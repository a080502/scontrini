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
        
    except Exception as e:
        print(f"Errore nell'inizializzazione del database: {e}")
        raise

@app.route('/')
def index():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute('SELECT * FROM scontrini')
        scontrini = cursor.fetchall()
        
        cursor.execute('SELECT * FROM scontrini ORDER BY data_inserimento DESC LIMIT 3')
        ultimi_scontrini = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        totale_versare = sum(float(s['importo_versare']) for s in scontrini)
        totale_incassare = sum(float(s['importo_incassare']) for s in scontrini)
        totale_incassato = sum(float(s['importo_incassare']) for s in scontrini if s['incassato'])
        totale_da_incassare = sum(float(s['importo_incassare']) for s in scontrini if not s['incassato'])
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
        
        if filtro == 'incassati':
            cursor.execute('SELECT * FROM scontrini WHERE incassato = TRUE ORDER BY data_scontrino DESC, data_inserimento DESC')
            titolo = "Scontrini Incassati"
        elif filtro == 'da_incassare':
            cursor.execute('SELECT * FROM scontrini WHERE incassato = FALSE ORDER BY data_scontrino DESC, data_inserimento DESC')
            titolo = "Scontrini da Incassare"
        else:
            cursor.execute('SELECT * FROM scontrini ORDER BY data_scontrino DESC, data_inserimento DESC')
            titolo = "Tutti gli Scontrini"
        
        scontrini = cursor.fetchall()
        cursor.close()
        conn.close()
        
        totale_versare = sum(float(s['importo_versare']) for s in scontrini)
        totale_incassare = sum(float(s['importo_incassare']) for s in scontrini)
        totale_incassato = sum(float(s['importo_incassare']) for s in scontrini if s['incassato'])
        totale_da_incassare = sum(float(s['importo_incassare']) for s in scontrini if not s['incassato'])
        
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
            data_scontrino = request.form['data_scontrino']
            nome_scontrino = request.form['nome_scontrino']
            importo_versare = float(request.form['importo_versare'])
            importo_incassare = float(request.form['importo_incassare'])
            
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
            data_scontrino = request.form['data_scontrino']
            nome_scontrino = request.form['nome_scontrino']
            importo_versare = float(request.form['importo_versare'])
            importo_incassare = float(request.form['importo_incassare'])
            
            cursor.execute('''
                UPDATE scontrini 
                SET data_scontrino=%s, nome_scontrino=%s, importo_versare=%s, importo_incassare=%s 
                WHERE id=%s
            ''', (data_scontrino, nome_scontrino, importo_versare, importo_incassare, id))
            conn.commit()
            cursor.close()
            conn.close()
            
            return redirect(url_for('lista_scontrini'))
        
        cursor.execute('SELECT * FROM scontrini WHERE id = %s', (id,))
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
        cursor.execute('SELECT * FROM scontrini ORDER BY data_scontrino DESC')
        scontrini = cursor.fetchall()
        cursor.close()
        conn.close()
        return jsonify([dict(s) for s in scontrini])
    except Exception as e:
        print(f"Errore nell'API: {e}")
        return jsonify({"errore": str(e)}), 500

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
    # Inizializza il database all'avvio
    try:
        init_db()
    except Exception as e:
        print(f"Errore nell'inizializzazione: {e}")
    
    # Avvia l'app
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
