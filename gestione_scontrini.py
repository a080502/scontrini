from flask import Flask, render_template, request, redirect, url_for, jsonify
import psycopg2
import psycopg2.extras
from datetime import datetime
import os
import urllib.parse

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'default-dev-key')

# Configurazione database
DATABASE_URL = os.environ.get('DATABASE_URL')

def get_db_connection():
    """Crea una connessione al database PostgreSQL"""
    if DATABASE_URL:
        # Parse dell'URL del database per psycopg2
        url = urllib.parse.urlparse(DATABASE_URL)
        conn = psycopg2.connect(
            host=url.hostname,
            port=url.port,
            database=url.path[1:],  # Rimuove il '/' iniziale
            user=url.username,
            password=url.password,
            cursor_factory=psycopg2.extras.RealDictCursor
        )
    else:
        # Fallback locale per sviluppo (puoi rimuoverlo se vuoi)
        raise ValueError("DATABASE_URL non configurato")
    
    return conn

def init_db():
    """Inizializza il database PostgreSQL"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
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
            WHERE table_name = 'scontrini'
        """)
        columns = [row['column_name'] for row in cursor.fetchall()]
        
        # Aggiungi colonne se non esistono
        if 'incassato' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN incassato BOOLEAN DEFAULT FALSE')
        if 'data_incasso' not in columns:
            cursor.execute('ALTER TABLE scontrini ADD COLUMN data_incasso TIMESTAMP NULL')
            
        conn.commit()
        cursor.close()
        conn.close()
        print("Database inizializzato con successo!")
        
    except Exception as e:
        print(f"Errore nell'inizializzazione del database: {e}")
        raise

@app.route('/')
def index():
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

@app.route('/lista')
def lista_scontrini():
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

@app.route('/aggiungi', methods=['GET', 'POST'])
def aggiungi_scontrino():
    if request.method == 'POST':
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
    
    return render_template('aggiungi.html')

@app.route('/modifica/<int:id>', methods=['GET', 'POST'])
def modifica_scontrino(id):
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

@app.route('/incassa/<int:id>')
def incassa_scontrino(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('UPDATE scontrini SET incassato = TRUE, data_incasso = CURRENT_TIMESTAMP WHERE id = %s', (id,))
    conn.commit()
    cursor.close()
    conn.close()
    return redirect(url_for('lista_scontrini'))

@app.route('/annulla_incasso/<int:id>')
def annulla_incasso(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('UPDATE scontrini SET incassato = FALSE, data_incasso = NULL WHERE id = %s', (id,))
    conn.commit()
    cursor.close()
    conn.close()
    return redirect(url_for('lista_scontrini'))

@app.route('/elimina/<int:id>')
def elimina_scontrino(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('DELETE FROM scontrini WHERE id = %s', (id,))
    conn.commit()
    cursor.close()
    conn.close()
    return redirect(url_for('lista_scontrini'))

@app.route('/api/scontrini')
def api_scontrini():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('SELECT * FROM scontrini ORDER BY data_scontrino DESC')
    scontrini = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify([dict(s) for s in scontrini])
