from flask import Flask, render_template, request, redirect, url_for, jsonify, session
import psycopg2
import psycopg2.extras
from datetime import datetime
import os
import urllib.parse

# Auth helpers
from werkzeug.security import generate_password_hash, check_password_hash
from functools import wraps

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
    """Inizializza il database PostgreSQL (tabelle scontrini e users)"""
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
                data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        # Crea la tabella users se non esiste
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')

        # Verifica se le colonne esistono (PostgreSQL usa information_schema)
        cursor.execute("""
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'scontrini' AND table_schema = 'public'
        """)
        columns = [row['column_name'] for row in cursor.fetchall()]
        
        # Aggiungi colonne se non esistono (compatibilità con vecchie versioni)
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

# ----------------------------
# Autenticazione e Autorizzazione
# ----------------------------
def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not session.get('user_id'):
            return redirect(url_for('login', next=request.path))
        return f(*args, **kwargs)
    return decorated_function

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not session.get('user_id'):
            return redirect(url_for('login'))
        try:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT is_admin FROM users WHERE id = %s", (session['user_id'],))
            row = cursor.fetchone()
            cursor.close()
            conn.close()
            if not row or not row.get('is_admin'):
                return "Permesso negato", 403
        except Exception:
            return "Errore autorizzazione", 500
        return f(*args, **kwargs)
    return decorated_function

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        username = request.form.get('username')
        # Password check disabilitato temporaneamente
        try:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT * FROM users WHERE username = %s", (username,))
            user = cursor.fetchone()
            cursor.close()
            conn.close()
            
            if user:  # Se l'utente esiste, accetta qualsiasi password
                session.clear()
                session['user_id'] = user['id']
                session['username'] = user['username']
                session['is_admin'] = bool(user.get('is_admin'))
                next_url = request.args.get('next') or url_for('index')
                return redirect(next_url)
            else:
                error = "Utente non trovato"
        except Exception as e:
            error = f"Errore di accesso: {e}"
            
    return render_template('login.html', error=error)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

# Il resto del codice rimane invariato...
[Il resto del file continua con le stesse funzioni che avevi prima]
