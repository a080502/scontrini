from flask import Flask, render_template, request, redirect, url_for, jsonify
import psycopg2
import psycopg2.extras
from datetime import datetime
import os
import urllib.parse

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'default-dev-key')

# Configurazione database - Render PostgreSQL
DATABASE_URL = os.environ.get('DATABASE_URL')

def get_db_connection():
    """Crea una connessione al database PostgreSQL"""
    if not DATABASE_URL:
        raise ValueError("DATABASE_URL non configurata nelle variabili d'ambiente")
    
    try:
        # Parse dell'URL del database per psycopg2
        url = urllib.parse.urlparse(DATABASE_URL)
        
        # Debug info (rimuovi in produzione)
        print(f"Tentativo connessione a: {url.hostname}:{url.port}")
        
        conn = psycopg2.connect(
            host=url.hostname,
            port=url.port,
            database=url.path[1:],  # Rimuove il '/' iniziale
            user=url.username,
            password=url.password,
            cursor_factory=psycopg2.extras.RealDictCursor,
            sslmode='require'  # Render richiede SSL
        )
        
        print("Connessione al database riuscita!")
        return conn
        
    except Exception as e:
        print(f"Errore nella connessione al database: {e}")
        raise

def validate_date(date_string):
    """Valida e corregge il formato della data"""
    try:
        # Prova a parsare la data
        parsed_date = datetime.strptime(date_string, '%Y-%m-%d')
        
        # Controlla se l'anno è ragionevole (tra 1900 e 2100)
        if parsed_date.year < 1900 or parsed_date.year > 2100:
            raise ValueError(f"Anno non valido: {parsed_date.year}")
            
        return parsed_date.strftime('%Y-%m-%d')
    except ValueError as e:
        print(f"Errore nella validazione della data '{date_string}': {e}")
        # Ritorna la data corrente come fallback
        return datetime.now().strftime('%Y-%m-%d')

def clean_database_dates():
    """Pulisce le date non valide nel database"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Trova record con date problematiche
        cursor.execute("""
            SELECT id, data_scontrino 
            FROM scontrini 
            WHERE EXTRACT(YEAR FROM data_scontrino) > 2100 
               OR EXTRACT(YEAR FROM data_scontrino) < 1900
        """)
        
        problematic_records = cursor.fetchall()
        
        if problematic_records:
            print(f"Trovati {len(problematic_records)} record con date problematiche")
            
            # Correggi le date problematiche impostandole alla data corrente
            for record in problematic_records:
                cursor.execute("""
                    UPDATE scontrini 
                    SET data_scontrino = CURRENT_DATE 
                    WHERE id = %s
                """, (record['id'],))
                print(f"Corretta data per record ID {record['id']}")
            
            conn.commit()
            print("Date corrette con successo!")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Errore nella pulizia delle date: {e}")

def init_db():
    """Inizializza il database PostgreSQL"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        print("Inizializzazione database...")
        
        # Crea la tabella se non esiste
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS scontrini (
                id SERIAL PRIMARY KEY,
                data_scontrino DATE NOT NULL,
                nome_scontrino TEXT NOT NULL,
                importo_versare DECIMAL(10,2) NOT NULL,
                importo_incassare DECIMAL(10,2) NOT NULL,
                incassato BOOLEAN DEFAULT FALSE,
                data_incasso TIMESTAMP NULL,
                data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        # Verifica se le colonne esistono (PostgreSQL usa information_schema)
        cursor.execute("""
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'scontrini' AND table_schema = 'public'
        """)
        columns = [row['column_name'] for row in cursor.fetchall()]
        
        # Aggiungi colonne se non esistono
        if 'incassato' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN incassato BOOLEAN DEFAULT FALSE')
            print("Colonna 'incassato' aggiunta")
            
        if 'data_incasso' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN data_incasso TIMESTAMP NULL')
            print("Colonna 'data_incasso' aggiunta")
            
        conn.commit()
        cursor.close()
        conn.close()
        print("Database inizializzato con successo!")
        
        # Pulisci le date problematiche
        clean_database_dates()
        
    except Exception as e:
        print(f"Errore nell'inizializzazione del database: {e}")
        raise

@app.route('/')
def index():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Usa COALESCE per gestire valori NULL e aggiungi controlli di validità
        cursor.execute('''
            SELECT *, 
                   CASE 
                       WHEN EXTRACT(YEAR FROM data_scontrino) > 2100 
                         OR EXTRACT(YEAR FROM data_scontrino) < 1900 
                       THEN CURRENT_DATE 
                       ELSE data_scontrino 
                   END as data_corretta
            FROM scontrini
        ''')
        scontrini = cursor.fetchall()
        
        cursor.execute('''
            SELECT *, 
                   CASE 
                       WHEN EXTRACT(YEAR FROM data_scontrino) > 2100 
                         OR EXTRACT(YEAR FROM data_scontrino) < 1900 
                       THEN CURRENT_DATE 
                       ELSE data_scontrino 
                   END as data_corretta
            FROM scontrini 
            ORDER BY data_inserimento DESC LIMIT 3
        ''')
        ultimi_scontrini = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        totale_versare = sum(float(s['importo_versare'] or 0) for s in scontrini)
        totale_incassare = sum(float(s['importo_incassare'] or 0) for s in scontrini)
        totale_incassato = sum(float(s['importo_incassare'] or 0) for s in scontrini if s['incassato'])
        totale_da_incassare = sum(float(s['importo_incassare'] or 0) for s in scontrini if not s['incassato'])
        num_scontrini = len(scontrini)
        num_incassati = sum(1 for s in scontrini if s['incassato'])
        num_da_incassare = num_scontrini - num_incassati
        
        return render_template('dashboard.html', 
                             ultimi_scontrini=ultimi_scontrini,
                             totale_versare=totale_versare,
                             totale_incassare=totale_incassare,
                             totale_incassato=totale_incassato,
                             totale_da_incassare=totale_da_incassare,
                             num_scontrini=num_scontrini,
                             num_incassati=num_incassati,
                             num_da_incassare=num_da_incassare)
    except Exception as e:
        print(f"Errore nella homepage: {e}")
        return f"Errore: {e}", 500

@app.route('/lista')
def lista_scontrini():
    try:
        filtro = request.args.get('filtro', 'tutti')
        conn = get_db_connection()
        cursor = conn.cursor()
        
        base_query = '''
            SELECT *, 
                   CASE 
                       WHEN EXTRACT(YEAR FROM data_scontrino) > 2100 
                         OR EXTRACT(YEAR FROM data_scontrino) < 1900 
                       THEN CURRENT_DATE 
                       ELSE data_scontrino 
                   END as data_corretta
            FROM scontrini
        '''
        
        if filtro == 'incassati':
            cursor.execute(base_query + ' WHERE incassato = TRUE ORDER BY data_scontrino DESC, data_inserimento DESC')
            titolo = "Scontrini Incassati"
        elif filtro == 'da_incassare':
            cursor.execute(base_query + ' WHERE incassato = FALSE ORDER BY data_scontrino DESC, data_inserimento DESC')
            titolo = "Scontrini da Incassare"
        else:
            cursor.execute(base_query + ' ORDER BY data_scontrino DESC, data_inserimento DESC')
            titolo = "Tutti gli Scontrini"
        
        scontrini = cursor.fetchall()
        cursor.close()
        conn.close()
        
        totale_versare = sum(float(s['importo_versare'] or 0) for s in scontrini)
        totale_incassare = sum(float(s['importo_incassare'] or 0) for s in scontrini)
        totale_incassato = sum(float(s['importo_incassare'] or 0) for s in scontrini if s['incassato'])
        totale_da_incassare = sum(float(s['importo_incassare'] or 0) for s in scontrini if not s['incassato'])
        
        return render_template('lista.html', 
                             scontrini=scontrini,
                             totale_versare=totale_versare,
                             totale_incassare=totale_incassare,
                             totale_incassato=totale_incassato,
                             totale_da_incassare=totale_da_incassare,
                             filtro=filtro,
                             titolo=titolo)
    except Exception as e:
        print(f"Errore nella lista: {e}")
        return f"Errore: {e}", 500

@app.route('/aggiungi', methods=['GET', 'POST'])
def aggiungi_scontrino():
    if request.method == 'POST':
        try:
            # Valida la data prima di inserirla
            data_scontrino_raw = request.form['data_scontrino']
            data_scontrino = validate_date(data_scontrino_raw)
            
            nome_scontrino = request.form['nome_scontrino']
            importo_versare = float(request.form['importo_versare'] or 0)
            importo_incassare = float(request.form['importo_incassare'] or 0)
            
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute('''
                INSERT INTO scontrini (data_scontrino, nome_scontrino, importo_versare, importo_incassare) 
                VALUES (%s, %s, %s, %s)
            ''', (data_scontrino, nome_scontrino, importo_versare, importo_incassare))
            conn.commit()
            cursor.close()
            conn.close()
            
            return redirect(url_for('lista_scontrini'))
        except Exception as e:
            print(f"Errore nell'aggiunta: {e}")
            return f"Errore: {e}", 500
    
    return render_template('aggiungi.html')

@app.route('/modifica/<int:id>', methods=['GET', 'POST'])
def modifica_scontrino(id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        if request.method == 'POST':
            # Valida la data prima di aggiornarla
            data_scontrino_raw = request.form['data_scontrino']
            data_scontrino = validate_date(data_scontrino_raw)
            
            nome_scontrino = request.form['nome_scontrino']
            importo_versare = float(request.form['importo_versare'] or 0)
            importo_incassare = float(request.form['importo_incassare'] or 0)
            
            cursor.execute('''
                UPDATE scontrini 
                SET data_scontrino=%s, nome_scontrino=%s, importo_versare=%s, importo_incassare=%s 
                WHERE id=%s
            ''', (data_scontrino, nome_scontrino, importo_versare, importo_incassare, id))
            conn.commit()
            cursor.close()
            conn.close()
            
            return redirect(url_for('lista_scontrini'))
        
        cursor.execute('''
            SELECT *, 
                   CASE 
                       WHEN EXTRACT(YEAR FROM data_scontrino) > 2100 
                         OR EXTRACT(YEAR FROM data_scontrino) < 1900 
                       THEN CURRENT_DATE 
                       ELSE data_scontrino 
                   END as data_corretta
            FROM scontrini WHERE id = %s
        ''', (id,))
        scontrino = cursor.fetchone()
        cursor.close()
        conn.close()
        
        if scontrino is None:
            return redirect(url_for('lista_scontrini'))
        
        return render_template('modifica.html', scontrino=scontrino)
    except Exception as e:
        print(f"Errore nella modifica: {e}")
        return f"Errore: {e}", 500

@app.route('/incassa/<int:id>')
def incassa_scontrino(id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('UPDATE scontrini SET incassato = TRUE, data_incasso = CURRENT_TIMESTAMP WHERE id = %s', (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    except Exception as e:
        print(f"Errore nell'incasso: {e}")
        return f"Errore: {e}", 500

@app.route('/annulla_incasso/<int:id>')
def annulla_incasso(id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('UPDATE scontrini SET incassato = FALSE, data_incasso = NULL WHERE id = %s', (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    except Exception as e:
        print(f"Errore nell'annullamento incasso: {e}")
        return f"Errore: {e}", 500

@app.route('/elimina/<int:id>')
def elimina_scontrino(id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('DELETE FROM scontrini WHERE id = %s', (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    except Exception as e:
        print(f"Errore nell'eliminazione: {e}")
        return f"Errore: {e}", 500

@app.route('/api/scontrini')
def api_scontrini():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('''
            SELECT *, 
                   CASE 
                       WHEN EXTRACT(YEAR FROM data_scontrino) > 2100 
                         OR EXTRACT(YEAR FROM data_scontrino) < 1900 
                       THEN CURRENT_DATE 
                       ELSE data_scontrino 
                   END as data_corretta
            FROM scontrini ORDER BY data_scontrino DESC
        ''')
        scontrini = cursor.fetchall()
        cursor.close()
        conn.close()
        return jsonify([dict(s) for s in scontrini])
    except Exception as e:
        print(f"Errore nell'API: {e}")
        return jsonify({"errore": str(e)}), 500

# Route di emergenza per diagnosticare il problema
@app.route('/diagnose')
def diagnose():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Controlla la struttura della tabella
        cursor.execute("""
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns 
            WHERE table_name = 'scontrini' AND table_schema = 'public'
        """)
        columns = cursor.fetchall()
        
        result = "=== DIAGNOSI DATABASE ===\n\n"
        result += "Struttura tabella scontrini:\n"
        for col in columns:
            result += f"- {col['column_name']}: {col['data_type']} (NULL: {col['is_nullable']})\n"
        
        # Conta i record totali
        cursor.execute("SELECT COUNT(*) as count FROM scontrini")
        count = cursor.fetchone()['count']
        result += f"\nTotale record: {count}\n"
        
        if count > 0:
            # Prova a selezionare solo un record per volta per identificare quello problematico
            cursor.execute("SELECT id FROM scontrini ORDER BY id")
            ids = cursor.fetchall()
            
            problematic_ids = []
            for record in ids:
                try:
                    cursor.execute("SELECT * FROM scontrini WHERE id = %s", (record['id'],))
                    single_record = cursor.fetchone()
                    # Prova ad accedere alla data
                    date_value = single_record['data_scontrino']
                    if date_value:
                        year = date_value.year
                        if year > 2100 or year < 1900:
                            problematic_ids.append(f"ID {record['id']}: anno {year}")
                except Exception as e:
                    problematic_ids.append(f"ID {record['id']}: errore {str(e)}")
            
            if problematic_ids:
                result += f"\nRecord problematici trovati ({len(problematic_ids)}):\n"
                for pid in problematic_ids[:10]:  # Mostra solo i primi 10
                    result += f"- {pid}\n"
                if len(problematic_ids) > 10:
                    result += f"... e altri {len(problematic_ids) - 10}\n"
            else:
                result += "\nNessun record problematico trovato nella verifica singola.\n"
        
        cursor.close()
        conn.close()
        
        return f"<pre>{result}</pre>"
        
    except Exception as e:
        return f"Errore nella diagnosi: {e}"

# Route per eliminare record problematici
@app.route('/delete-problematic')
def delete_problematic():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Prima conta quanti sono
        cursor.execute("""
            SELECT COUNT(*) as count FROM scontrini 
            WHERE EXTRACT(YEAR FROM data_scontrino) > 2100 
               OR EXTRACT(YEAR FROM data_scontrino) < 1900
        """)
        count_before = cursor.fetchone()['count']
        
        if count_before > 0:
            # Elimina i record problematici
            cursor.execute("""
                DELETE FROM scontrini 
                WHERE EXTRACT(YEAR FROM data_scontrino) > 2100 
                   OR EXTRACT(YEAR FROM data_scontrino) < 1900
            """)
            conn.commit()
            
            return f"Eliminati {count_before} record con date problematiche."
        else:
            return "Nessun record problematico trovato da eliminare."
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        return f"Errore nell'eliminazione: {e}"

# Test di base
@app.route('/test')
def test():
    return "Flask funziona!"

# Test di connessione
@app.route('/test-db')
def test_db():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('SELECT version();')
        version = cursor.fetchone()
        cursor.close()
        conn.close()
        return f"Database connesso! Versione PostgreSQL: {version}"
    except Exception as e:
        return f"Errore connessione database: {e}"

if __name__ == '__main__':
    # Inizializza il database all'avvio solo in sviluppo
    try:
        init_db()
    except Exception as e:
        print(f"Errore nell'inizializzazione: {e}")
    
    # Avvia l'app
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
