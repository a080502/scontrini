from flask import Flask, render_template, request, redirect, url_for, session, jsonify, g
from werkzeug.security import generate_password_hash, check_password_hash
from functools import wraps
import psycopg2
from psycopg2.extras import DictCursor
import os
from datetime import datetime

app = Flask(__name__)
app.secret_key = 'chiave_segreta_da_cambiare'  # Cambia questa chiave in produzione

# Configurazione del database
DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_NAME = os.environ.get('DB_NAME', 'scontrini_db')
DB_USER = os.environ.get('DB_USER', 'postgres')
DB_PASS = os.environ.get('DB_PASS', 'password')

def get_db_connection():
    conn = psycopg2.connect(
        host=DB_HOST,
        database=DB_NAME,
        user=DB_USER,
        password=DB_PASS,
        cursor_factory=DictCursor
    )
    return conn

def init_db():
    """
    Inizializza il database creando le tabelle necessarie
    """
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
        
        # Crea un utente admin di default se non esiste
        default_password_hash = generate_password_hash('admin')
        cursor.execute('''
            INSERT INTO users (username, password_hash, is_admin)
            VALUES (%s, %s, TRUE)
            ON CONFLICT (username) DO NOTHING
        ''', ('admin', default_password_hash))
        
        conn.commit()
        cursor.close()
        conn.close()
        print("Inizializzazione completata!")
        
    except Exception as e:
        print(f"Errore nell'inizializzazione del database: {e}")
        raise

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

@app.route('/')
@login_required
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

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        try:
            conn = get_db_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT * FROM users WHERE username = %s", (username,))
            user = cursor.fetchone()
            cursor.close()
            conn.close()
            
            # TEMPORANEAMENTE DISABILITATO IL CONTROLLO PASSWORD
            # if not user or not check_password_hash(user['password_hash'], password):
            if not user:  # Controlla solo se l'utente esiste
                error = "Credenziali non valide"
                return render_template('login.html', error=error)
                
            # Imposta la sessione
            session.clear()
            session['user_id'] = user['id']
            session['username'] = user['username']
            session['is_admin'] = bool(user.get('is_admin'))
            
            next_url = request.args.get('next') or url_for('index')
            return redirect(next_url)
        except Exception as e:
            error = f"Errore: {e}"
            return render_template('login.html', error=error)
    return render_template('login.html', error=error)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

@app.route('/lista')
@login_required
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
@login_required
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
@login_required
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
@login_required
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
@login_required
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
        print(f"Errore nell'annullo incasso: {e}")
        return f"Errore: {e}", 500

@app.route('/elimina/<int:id>')
@login_required
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
@login_required
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

# ----------------------------
# Gestione utenti (admin only)
# ----------------------------
@app.route('/users')
@admin_required
def users():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id, username, is_admin, created_at FROM users ORDER BY id")
        users = cursor.fetchall()
        cursor.close()
        conn.close()
        return render_template('users.html', users=users, current_user_id=session.get('user_id'))
    except Exception as e:
        return f"Errore: {e}", 500

@app.route('/users/create', methods=['GET', 'POST'])
@admin_required
def user_create():
    error = None
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        password = request.form.get('password')
        is_admin = bool(request.form.get('is_admin'))
        if not username or not password:
            error = "Compila username e password"
        else:
            pw_hash = generate_password_hash(password)
            try:
                conn = get_db_connection()
                cursor = conn.cursor()
                cursor.execute("INSERT INTO users (username, password_hash, is_admin) VALUES (%s, %s, %s)",
                               (username, pw_hash, is_admin))
                conn.commit()
                cursor.close()
                conn.close()
                return redirect(url_for('users'))
            except Exception as e:
                error = f"Errore creazione utente: {e}"
    return render_template('user_form.html', user=None, error=error)

@app.route('/users/edit/<int:id>', methods=['GET', 'POST'])
@admin_required
def user_edit(id):
    error = None
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        if request.method == 'POST':
            username = request.form.get('username', '').strip()
            password = request.form.get('password')
            is_admin = bool(request.form.get('is_admin'))
            if not username:
                error = "Username obbligatorio"
            else:
                if password:
                    pw_hash = generate_password_hash(password)
                    cursor.execute("UPDATE users SET username=%s, password_hash=%s, is_admin=%s WHERE id=%s",
                                   (username, pw_hash, is_admin, id))
                else:
                    cursor.execute("UPDATE users SET username=%s, is_admin=%s WHERE id=%s",
                                   (username, is_admin, id))
                conn.commit()
                cursor.close()
                conn.close()
                return redirect(url_for('users'))
        else:
            cursor.execute("SELECT id, username, is_admin, created_at FROM users WHERE id=%s", (id,))
            user = cursor.fetchone()
            cursor.close()
            conn.close()
            if not user:
                return "Utente non trovato", 404
            return render_template('user_form.html', user=user, error=None)
    except Exception as e:
        return f"Errore: {e}", 500

@app.route('/users/delete/<int:id>')
@admin_required
def user_delete(id):
    # evita che l'admin si cancelli da solo
    if session.get('user_id') == id:
        return "Impossibile eliminare l'utente connesso", 400
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("DELETE FROM users WHERE id=%s", (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(url_for('users'))
    except Exception as e:
        return f"Errore: {e}", 500

if __name__ == '__main__':
    # Inizializza il database all'avvio solo in sviluppo
    try:
        init_db()
    except Exception as e:
        print(f"Errore nell'inizializzazione: {e}")
    
    # Avvia l'app
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
