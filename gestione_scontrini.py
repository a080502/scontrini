from flask import Flask, render_template, request, redirect, url_for, jsonify
import psycopg2
import psycopg2.extras
from datetime import datetime
import os
import urllib.parse
from werkzeug.security import generate_password_hash

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'default-dev-key')

# Configurazione database - Render PostgreSQL
DATABASE_URL = os.environ.get('DATABASE_URL')

def get_db_connection():
    """Crea una connessione al database PostgreSQL"""
    if not DATABASE_URL:
        raise ValueError("DATABASE_URL non configurata nelle variabili d'ambiente")
    
    try:
        url = urllib.parse.urlparse(DATABASE_URL)
        conn = psycopg2.connect(
            host=url.hostname,
            port=url.port,
            database=url.path[1:],
            user=url.username,
            password=url.password,
            cursor_factory=psycopg2.extras.RealDictCursor,
            sslmode='require'
        )
        return conn
    except Exception as e:
        print(f"Errore nella connessione al database: {e}")
        raise

def validate_date(date_string):
    """Valida e corregge il formato della data"""
    try:
        parsed_date = datetime.strptime(date_string, '%Y-%m-%d')
        if parsed_date.year < 1900 or parsed_date.year > 2100:
            raise ValueError(f"Anno non valido: {parsed_date.year}")
        return parsed_date.strftime('%Y-%m-%d')
    except (ValueError, TypeError):
        return datetime.now().strftime('%Y-%m-%d')

def init_db():
    """Inizializza il database PostgreSQL"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        print("Inizializzazione database...")
        
        # Crea la tabella scontrini se non esiste
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS scontrini (
                id SERIAL PRIMARY KEY,
                data_scontrino DATE NOT NULL,
                nome_scontrino TEXT NOT NULL,
                importo_versare DECIMAL(10,2) NOT NULL,
                importo_incassare DECIMAL(10,2) NOT NULL,
                incassato BOOLEAN DEFAULT FALSE,
                data_incasso TIMESTAMP NULL,
                versato BOOLEAN DEFAULT FALSE,
                data_versamento TIMESTAMP NULL,
                data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        # Crea la tabella users se non esiste
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                filiale TEXT,
                utente TEXT,
                nome_utente TEXT,
                mail TEXT UNIQUE,
                password_hash TEXT,
                campo_libero1 TEXT,
                campo_libero2 TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        # Verifica e aggiungi colonne mancanti alla tabella scontrini
        cursor.execute("""
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'scontrini' AND table_schema = 'public'
        """)
        columns = [row['column_name'] for row in cursor.fetchall()]
        
        if 'incassato' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN incassato BOOLEAN DEFAULT FALSE')
        if 'data_incasso' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN data_incasso TIMESTAMP NULL')
        if 'versato' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN versato BOOLEAN DEFAULT FALSE')
        if 'data_versamento' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN data_versamento TIMESTAMP NULL')
            
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
        
        cursor.execute('SELECT * FROM scontrini ORDER BY data_inserimento DESC LIMIT 5')
        ultimi_scontrini = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        # Calcoli finanziari
        totale_da_versare_complessivo = sum(float(s['importo_versare'] or 0) for s in scontrini)
        totale_incassare = sum(float(s['importo_incassare'] or 0) for s in scontrini)
        totale_incassato = sum(float(s['importo_incassare'] or 0) for s in scontrini if s['incassato'])
        totale_da_incassare = totale_incassare - totale_incassato
        
        totale_versato = sum(float(s['importo_versare'] or 0) for s in scontrini if s['versato'])
        ancora_da_versare = totale_da_versare_complessivo - totale_versato
        cassa = totale_incassato - totale_versato

        # Statistiche
        num_scontrini = len(scontrini)
        num_incassati = sum(1 for s in scontrini if s['incassato'])
        num_da_incassare = num_scontrini - num_incassati
        
        return render_template('dashboard.html', 
                             ultimi_scontrini=ultimi_scontrini,
                             totale_da_versare_complessivo=totale_da_versare_complessivo,
                             totale_incassare=totale_incassare,
                             totale_incassato=totale_incassato,
                             totale_da_incassare=totale_da_incassare,
                             totale_versato=totale_versato,
                             ancora_da_versare=ancora_da_versare,
                             cassa=cassa,
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
        
        base_query = 'SELECT * FROM scontrini'
        
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
        
        # Calcoli finanziari basati sul filtro
        totale_versare_filtrato = sum(float(s['importo_versare'] or 0) for s in scontrini)
        totale_incassare_filtrato = sum(float(s['importo_incassare'] or 0) for s in scontrini)
        totale_incassato = sum(float(s['importo_incassare'] or 0) for s in scontrini if s['incassato'])
        totale_da_incassare = totale_incassare_filtrato - totale_incassato
        totale_versato = sum(float(s['importo_versare'] or 0) for s in scontrini if s['versato'])
        ancora_da_versare = totale_versare_filtrato - totale_versato
        cassa = totale_incassato - totale_versato

        return render_template('lista.html', 
                             scontrini=scontrini,
                             totale_versare_filtrato=totale_versare_filtrato,
                             totale_incassare_filtrato=totale_incassare_filtrato,
                             totale_incassato=totale_incassato,
                             totale_da_incassare=totale_da_incassare,
                             totale_versato=totale_versato,
                             ancora_da_versare=ancora_da_versare,
                             cassa=cassa,
                             filtro=filtro,
                             titolo=titolo)
    except Exception as e:
        print(f"Errore nella lista: {e}")
        return f"Errore: {e}", 500

# --- NUOVE ROUTE PER IL VERSAMENTO ---

@app.route('/versa/<int:id>')
def versa_scontrino(id):
    """Imposta uno scontrino come versato."""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('UPDATE scontrini SET versato = TRUE, data_versamento = CURRENT_TIMESTAMP WHERE id = %s', (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(request.referrer or url_for('lista_scontrini'))
    except Exception as e:
        print(f"Errore nel versamento: {e}")
        return f"Errore: {e}", 500

@app.route('/annulla_versamento/<int:id>')
def annulla_versamento(id):
    """Annulla lo stato di versato per uno scontrino."""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('UPDATE scontrini SET versato = FALSE, data_versamento = NULL WHERE id = %s', (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(request.referrer or url_for('lista_scontrini'))
    except Exception as e:
        print(f"Errore nell'annullamento versamento: {e}")
        return f"Errore: {e}", 500

# --- ROUTE ESISTENTI (invariate o con modifiche minori) ---

@app.route('/aggiungi', methods=['GET', 'POST'])
def aggiungi_scontrino():
    if request.method == 'POST':
        try:
            data_scontrino = validate_date(request.form['data_scontrino'])
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
            data_scontrino = validate_date(request.form['data_scontrino'])
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
        return redirect(request.referrer or url_for('lista_scontrini'))
    except Exception as e:
        print(f"Errore nell'incasso: {e}")
        return f"Errore: {e}", 500

@app.route('/annulla_incasso/<int:id>')
def annulla_incasso(id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        # Quando si annulla un incasso, si annulla anche il versamento associato
        cursor.execute('UPDATE scontrini SET incassato = FALSE, data_incasso = NULL, versato = FALSE, data_versamento = NULL WHERE id = %s', (id,))
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

# ... (tutte le altre route per la gestione utenti rimangono invariate) ...
@app.route('/aggiungi-utente', methods=['GET', 'POST'])
def aggiungi_utente():
    """Pagina per aggiungere un utente al database (crea la tabella users se non presente)"""
    if request.method == 'POST':
        filiale = request.form.get('filiale')
        utente = request.form.get('utente')
        nome_utente = request.form.get('nome_utente')
        mail = request.form.get('mail')
        password = request.form.get('password')
        campo1 = request.form.get('campo_libero1')
        campo2 = request.form.get('campo_libero2')

        if not mail or not password:
            error = "Email e password sono obbligatori."
            return render_template('aggiungi_utente.html', error=error, form=request.form)
        
        password_hash = generate_password_hash(password)

        conn = None
        cursor = None
        try:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute('''
                INSERT INTO users (filiale, utente, nome_utente, mail, password_hash, campo_libero1, campo_libero2)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            ''', (filiale, utente, nome_utente, mail, password_hash, campo1, campo2))
            conn.commit()
            return redirect(url_for('index'))
        except psycopg2.IntegrityError as e:
            if conn:
                conn.rollback()
            error = "Errore: la mail risulta già presente nel database."
            return render_template('aggiungi_utente.html', error=error, form=request.form)
        except Exception as e:
            if conn:
                conn.rollback()
            print(f"Errore aggiunta utente: {e}")
            return f"Errore: {e}", 500
        finally:
            if cursor:
                cursor.close()
            if conn:
                conn.close()

    return render_template('aggiungi_utente.html')

@app.route('/lista-utenti')
def lista_utenti():
    """Visualizza la lista di tutti gli utenti registrati"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('SELECT id, filiale, utente, nome_utente, mail, campo_libero1, campo_libero2, created_at FROM users ORDER BY created_at DESC')
        utenti = cursor.fetchall()
        cursor.close()
        conn.close()
        return render_template('lista_utenti.html', utenti=utenti)
    except Exception as e:
        print(f"Errore nella lista utenti: {e}")
        return f"Errore nel caricamento degli utenti: {e}", 500

if __name__ == '__main__':
    try:
        init_db()
    except Exception as e:
        print(f"Errore fatale nell'inizializzazione del DB: {e}")
    
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
