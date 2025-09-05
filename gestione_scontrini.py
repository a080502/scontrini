from flask import Flask, render_template, request, redirect, url_for, jsonify
import sqlite3
from datetime import datetime
import os

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'default-dev-key')

APP_DIR = os.path.dirname(os.path.abspath(__file__))
DATABASE = os.path.join(APP_DIR, 'scontrini.db')

def init_db():
    conn = sqlite3.connect(DATABASE)
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS scontrini (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data_scontrino DATE NOT NULL,
            nome_scontrino TEXT NOT NULL,
            importo_versare DECIMAL(10,2) NOT NULL,
            importo_incassare DECIMAL(10,2) NOT NULL,
            incassato BOOLEAN DEFAULT 0,
            data_incasso TIMESTAMP NULL,
            data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    cursor.execute("PRAGMA table_info(scontrini)")
    columns = [column[1] for column in cursor.fetchall()]
    if 'incassato' not in columns:
        cursor.execute('ALTER TABLE scontrini ADD COLUMN incassato BOOLEAN DEFAULT 0')
    if 'data_incasso' not in columns:
        cursor.execute('ALTER TABLE scontrini ADD COLUMN data_incasso TIMESTAMP NULL')
    conn.commit()
    conn.close()

def get_db_connection():
    conn = sqlite3.connect(DATABASE)
    conn.row_factory = sqlite3.Row
    return conn

@app.route('/')
def index():
    conn = get_db_connection()
    scontrini = conn.execute('SELECT * FROM scontrini').fetchall()
    ultimi_scontrini = conn.execute('SELECT * FROM scontrini ORDER BY data_inserimento DESC LIMIT 3').fetchall()
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
    if filtro == 'incassati':
        scontrini = conn.execute('SELECT * FROM scontrini WHERE incassato = 1 ORDER BY data_scontrino DESC, data_inserimento DESC').fetchall()
        titolo = "Scontrini Incassati"
    elif filtro == 'da_incassare':
        scontrini = conn.execute('SELECT * FROM scontrini WHERE incassato = 0 ORDER BY data_scontrino DESC, data_inserimento DESC').fetchall()
        titolo = "Scontrini da Incassare"
    else:
        scontrini = conn.execute('SELECT * FROM scontrini ORDER BY data_scontrino DESC, data_inserimento DESC').fetchall()
        titolo = "Tutti gli Scontrini"
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
        conn.execute('INSERT INTO scontrini (data_scontrino, nome_scontrino, importo_versare, importo_incassare) VALUES (?, ?, ?, ?)',
                     (data_scontrino, nome_scontrino, importo_versare, importo_incassare))
        conn.commit()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    return render_template('aggiungi.html')

@app.route('/modifica/<int:id>', methods=['GET', 'POST'])
def modifica_scontrino(id):
    conn = get_db_connection()
    if request.method == 'POST':
        data_scontrino = request.form['data_scontrino']
        nome_scontrino = request.form['nome_scontrino']
        importo_versare = float(request.form['importo_versare'])
        importo_incassare = float(request.form['importo_incassare'])
        conn.execute('UPDATE scontrini SET data_scontrino=?, nome_scontrino=?, importo_versare=?, importo_incassare=? WHERE id=?',
                     (data_scontrino, nome_scontrino, importo_versare, importo_incassare, id))
        conn.commit()
        conn.close()
        return redirect(url_for('lista_scontrini'))
    scontrino = conn.execute('SELECT * FROM scontrini WHERE id = ?', (id,)).fetchone()
    conn.close()
    if scontrino is None:
        return redirect(url_for('lista_scontrini'))
    return render_template('modifica.html', scontrino=scontrino)

@app.route('/incassa/<int:id>')
def incassa_scontrino(id):
    conn = get_db_connection()
    conn.execute('UPDATE scontrini SET incassato = 1, data_incasso = CURRENT_TIMESTAMP WHERE id = ?', (id,))
    conn.commit()
    conn.close()
    return redirect(url_for('lista_scontrini'))

@app.route('/annulla_incasso/<int:id>')
def annulla_incasso(id):
    conn = get_db_connection()
    conn.execute('UPDATE scontrini SET incassato = 0, data_incasso = NULL WHERE id = ?', (id,))
    conn.commit()
    conn.close()
    return redirect(url_for('lista_scontrini'))

@app.route('/elimina/<int:id>')
def elimina_scontrino(id):
    conn = get_db_connection()
    conn.execute('DELETE FROM scontrini WHERE id = ?', (id,))
    conn.commit()
    conn.close()
    return redirect(url_for('lista_scontrini'))

@app.route('/api/scontrini')
def api_scontrini():
    conn = get_db_connection()
    scontrini = conn.execute('SELECT * FROM scontrini ORDER BY data_scontrino DESC').fetchall()
    conn.close()
    return jsonify([dict(s) for s in scontrini])
