from flask import Flask, render_template, request, redirect, url_for, jsonify
import psycopg2
import psycopg2.extras
from datetime import datetime
import os
import urllib.parse
from werkzeug.security import generate_password_hash
from collections import defaultdict

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'default-dev-key')

DATABASE_URL = os.environ.get('DATABASE_URL')

def get_db_connection():
    if not DATABASE_URL:
        raise ValueError("DATABASE_URL non configurata")
    try:
        url = urllib.parse.urlparse(DATABASE_URL)
        conn = psycopg2.connect(
            host=url.hostname, port=url.port, database=url.path[1:],
            user=url.username, password=url.password,
            cursor_factory=psycopg2.extras.RealDictCursor, sslmode='require'
        )
        return conn
    except Exception as e:
        print(f"Errore connessione DB: {e}")
        raise

def validate_date(date_string):
    try:
        parsed_date = datetime.strptime(date_string, '%Y-%m-%d')
        if not (1900 < parsed_date.year < 2100):
            raise ValueError("Anno non valido")
        return parsed_date.strftime('%Y-%m-%d')
    except (ValueError, TypeError):
        return datetime.now().strftime('%Y-%m-%d')

def init_db():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS scontrini (
            id SERIAL PRIMARY KEY, data_scontrino DATE NOT NULL,
            nome_scontrino TEXT NOT NULL, importo_versare DECIMAL(10,2) NOT NULL,
            importo_incassare DECIMAL(10,2) NOT NULL, incassato BOOLEAN DEFAULT FALSE,
            data_incasso TIMESTAMP NULL, versato BOOLEAN DEFAULT FALSE,
            data_versamento TIMESTAMP NULL, data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY, filiale TEXT, utente TEXT, nome_utente TEXT,
            mail TEXT UNIQUE, password_hash TEXT, campo_libero1 TEXT, campo_libero2 TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    cursor.execute("SELECT column_name FROM information_schema.columns WHERE table_name = 'scontrini'")
    columns = [row['column_name'] for row in cursor.fetchall()]
    if 'versato' not in columns:
        cursor.execute('ALTER TABLE scontrini ADD COLUMN versato BOOLEAN DEFAULT FALSE')
    if 'data_versamento' not in columns:
        cursor.execute('ALTER TABLE scontrini ADD COLUMN data_versamento TIMESTAMP NULL')
    conn.commit()
    cursor.close()
    conn.close()
    print("Database inizializzato.")

@app.route('/')
def index():
    conn = get_db_connection()
    cursor = conn.cursor()
    # Query per scontrini ATTIVI
    cursor.execute('SELECT * FROM scontrini WHERE NOT (incassato = TRUE AND versato = TRUE)')
    scontrini_attivi = cursor.fetchall()
    
    # Query per ultimi 5 inseriti, indipendentemente dallo stato
    cursor.execute('SELECT * FROM scontrini ORDER BY data_inserimento DESC LIMIT 5')
    ultimi_scontrini = cursor.fetchall()

    # Conteggio archiviati
    cursor.execute('SELECT count(*) as total FROM scontrini WHERE incassato = TRUE AND versato = TRUE')
    num_archiviati = cursor.fetchone()['total']
    
    conn.close()

    # I calcoli ora si basano solo sugli scontrini attivi
    totale_incassare = sum(float(s['importo_incassare'] or 0) for s in scontrini_attivi)
    totale_incassato = sum(float(s['importo_incassare'] or 0) for s in scontrini_attivi if s['incassato'])
    totale_da_incassare = totale_incassare - totale_incassato
    totale_versato = sum(float(s['importo_versare'] or 0) for s in scontrini_attivi if s['versato'])
    totale_da_versare = sum(float(s['importo_versare'] or 0) for s in scontrini_attivi)
    ancora_da_versare = totale_da_versare - totale_versato
    cassa = totale_incassato - totale_versato
    
    num_scontrini = len(scontrini_attivi)
    num_incassati = sum(1 for s in scontrini_attivi if s['incassato'])
    num_da_incassare = num_scontrini - num_incassati

    return render_template('dashboard.html', 
                         ultimi_scontrini=ultimi_scontrini,
                         totale_incassare=totale_incassare, totale_incassato=totale_incassato,
                         totale_da_incassare=totale_da_incassare, totale_versato=totale_versato,
                         ancora_da_versare=ancora_da_versare, cassa=cassa,
                         num_scontrini=num_scontrini, num_incassati=num_incassati,
                         num_da_incassare=num_da_incassare, num_archiviati=num_archiviati)

@app.route('/lista')
def lista_scontrini():
    filtro = request.args.get('filtro', 'tutti')
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # La query di base ora esclude gli scontrini archiviati
    base_query = 'SELECT * FROM scontrini WHERE NOT (incassato = TRUE AND versato = TRUE)'
    order_clause = ' ORDER BY nome_scontrino, data_scontrino DESC'
    
    if filtro == 'incassati':
        cursor.execute(f"{base_query} AND incassato = TRUE {order_clause}")
        titolo = "Scontrini Attivi Incassati"
    elif filtro == 'da_incassare':
        cursor.execute(f"{base_query} AND incassato = FALSE {order_clause}")
        titolo = "Scontrini Attivi da Incassare"
    else:
        cursor.execute(base_query + order_clause)
        titolo = "Tutti gli Scontrini Attivi"
    
    scontrini = cursor.fetchall()
    conn.close()
    
    scontrini_raggruppati = defaultdict(lambda: {'scontrini': [], 'subtotali': defaultdict(float)})
    for s in scontrini:
        nome = s['nome_scontrino']
        gruppo = scontrini_raggruppati[nome]
        gruppo['scontrini'].append(s)
        importo_versare = float(s['importo_versare'] or 0)
        importo_incassare = float(s['importo_incassare'] or 0)
        gruppo['subtotali']['importo_versare'] += importo_versare
        gruppo['subtotali']['importo_incassare'] += importo_incassare
        if s['incassato']: gruppo['subtotali']['incassato'] += importo_incassare
        if s['versato']: gruppo['subtotali']['versato'] += importo_versare

    for nome, gruppo in scontrini_raggruppati.items():
        gruppo['subtotali']['cassa'] = gruppo['subtotali']['incassato'] - gruppo['subtotali']['versato']
    
    totale_incassato = sum(float(s['importo_incassare'] or 0) for s in scontrini if s['incassato'])
    totale_versato = sum(float(s['importo_versare'] or 0) for s in scontrini if s['versato'])
    cassa = totale_incassato - totale_versato

    return render_template('lista.html', 
                         scontrini_raggruppati=scontrini_raggruppati,
                         cassa=cassa, filtro=filtro, titolo=titolo,
                         num_elementi=len(scontrini))

# --- NUOVA ROUTE PER L'ARCHIVIO ---
@app.route('/archivio')
def archivio():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('SELECT * FROM scontrini WHERE incassato = TRUE AND versato = TRUE ORDER BY nome_scontrino, data_versamento DESC')
    scontrini = cursor.fetchall()
    conn.close()

    scontrini_raggruppati = defaultdict(lambda: {'scontrini': [], 'subtotali': defaultdict(float)})
    for s in scontrini:
        nome = s['nome_scontrino']
        gruppo = scontrini_raggruppati[nome]
        gruppo['scontrini'].append(s)
        gruppo['subtotali']['importo_versare'] += float(s['importo_versare'] or 0)
        gruppo['subtotali']['importo_incassare'] += float(s['importo_incassare'] or 0)
    
    totale_versato_archivio = sum(float(s['importo_versare'] or 0) for s in scontrini)
    totale_incassato_archivio = sum(float(s['importo_incassare'] or 0) for s in scontrini)

    return render_template('archivio.html',
                         scontrini_raggruppati=scontrini_raggruppati,
                         totale_versato_archivio=totale_versato_archivio,
                         totale_incassato_archivio=totale_incassato_archivio,
                         num_elementi=len(scontrini))

# --- GESTIONE SCONTRINI (Aggiungi, Modifica, etc.) ---
@app.route('/aggiungi', methods=['GET', 'POST'])
def aggiungi_scontrino():
    if request.method == 'POST':
        data_scontrino = validate_date(request.form['data_scontrino'])
        nome_scontrino = request.form['nome_scontrino']
        importo_versare = float(request.form['importo_versare'] or 0)
        importo_incassare = float(request.form['importo_incassare'] or 0)
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('INSERT INTO scontrini (data_scontrino, nome_scontrino, importo_versare, importo_incassare) VALUES (%s, %s, %s, %s)',
                       (data_scontrino, nome_scontrino, importo_versare, importo_incassare))
        conn.commit()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    return render_template('aggiungi.html')

@app.route('/incassa/<int:id>')
def incassa_scontrino(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('UPDATE scontrini SET incassato = TRUE, data_incasso = CURRENT_TIMESTAMP WHERE id = %s', (id,))
    conn.commit()
    conn.close()
    return redirect(request.referrer or url_for('lista_scontrini'))

@app.route('/annulla_incasso/<int:id>')
def annulla_incasso(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    # Questa azione ora funge anche da "ripristina dall'archivio"
    cursor.execute('UPDATE scontrini SET incassato = FALSE, data_incasso = NULL, versato = FALSE, data_versamento = NULL WHERE id = %s', (id,))
    conn.commit()
    conn.close()
    return redirect(request.referrer or url_for('lista_scontrini'))

@app.route('/versa/<int:id>')
def versa_scontrino(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('UPDATE scontrini SET versato = TRUE, data_versamento = CURRENT_TIMESTAMP WHERE id = %s', (id,))
    conn.commit()
    conn.close()
    return redirect(request.referrer or url_for('lista_scontrini'))

@app.route('/annulla_versamento/<int:id>')
def annulla_versamento(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('UPDATE scontrini SET versato = FALSE, data_versamento = NULL WHERE id = %s', (id,))
    conn.commit()
    conn.close()
    return redirect(request.referrer or url_for('lista_scontrini'))

# ... resto delle funzioni (modifica, elimina, gestione utenti) invariate ...
@app.route('/modifica/<int:id>', methods=['GET', 'POST'])
def modifica_scontrino(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    if request.method == 'POST':
        data_scontrino = validate_date(request.form['data_scontrino'])
        nome_scontrino = request.form['nome_scontrino']
        importo_versare = float(request.form['importo_versare'] or 0)
        importo_incassare = float(request.form['importo_incassare'] or 0)
        cursor.execute('UPDATE scontrini SET data_scontrino=%s, nome_scontrino=%s, importo_versare=%s, importo_incassare=%s WHERE id=%s',
                       (data_scontrino, nome_scontrino, importo_versare, importo_incassare, id))
        conn.commit()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    cursor.execute('SELECT * FROM scontrini WHERE id = %s', (id,))
    scontrino = cursor.fetchone()
    conn.close()
    return render_template('modifica.html', scontrino=scontrino)

@app.route('/elimina/<int:id>')
def elimina_scontrino(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('DELETE FROM scontrini WHERE id = %s', (id,))
    conn.commit()
    conn.close()
    return redirect(url_for('lista_scontrini'))

if __name__ == '__main__':
    init_db()
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)

