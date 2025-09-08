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
        parsed_date = datetime.strptime(date_string, '%Y-%m-%d')
        if parsed_date.year < 1900 or parsed_date.year > 2100:
            raise ValueError(f"Anno non valido: {parsed_date.year}")
        return parsed_date.strftime('%Y-%m-%d')
    except ValueError as e:
        print(f"Errore nella validazione della data '{date_string}': {e}")
        return datetime.now().strftime('%Y-%m-%d')

def clean_database_dates():
    """Pulisce le date non valide nel database"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT id, data_scontrino 
            FROM scontrini 
            WHERE EXTRACT(YEAR FROM data_scontrino) > 2100 
               OR EXTRACT(YEAR FROM data_scontrino) < 1900
        """)
        problematic_records = cursor.fetchall()
        if problematic_records:
            for record in problematic_records:
                cursor.execute("""
                    UPDATE scontrini 
                    SET data_scontrino = CURRENT_DATE 
                    WHERE id = %s
                """, (record['id'],))
            conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Errore nella pulizia delle date: {e}")

def init_db():
    """Inizializza il database PostgreSQL"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
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
        
        # Verifica e aggiunge colonne mancanti nella tabella scontrini (compatibilità)
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
            
        conn.commit()
        cursor.close()
        conn.close()
        clean_database_dates()
    except Exception as e:
        print(f"Errore nell'inizializzazione del database: {e}")
        raise

@app.route('/')
def index():
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
            return redirect(url_for('lista_utenti'))
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

@app.route('/utenti')
def lista_utenti():
    """Mostra la lista degli utenti"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('''
            SELECT id, filiale, utente, nome_utente, mail, campo_libero1, campo_libero2, created_at
            FROM users
            ORDER BY created_at DESC
        ''')
        users = cursor.fetchall()
        cursor.close()
        conn.close()
        return render_template('utenti.html', users=users)
    except Exception as e:
        print(f"Errore nella lista utenti: {e}")
        return f"Errore: {e}", 500

@app.route('/modifica-utente/<int:id>', methods=['GET', 'POST'])
def modifica_utente(id):
    """Modifica un utente esistente"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        if request.method == 'POST':
            filiale = request.form.get('filiale')
            utente = request.form.get('utente')
            nome_utente = request.form.get('nome_utente')
            mail = request.form.get('mail')
            password = request.form.get('password')
            campo1 = request.form.get('campo_libero1')
            campo2 = request.form.get('campo_libero2')

            if not mail:
                error = "La mail è obbligatoria."
                # riprendi i dati attuali per il form
                cursor.execute('SELECT * FROM users WHERE id = %s', (id,))
                user = cursor.fetchone()
                cursor.close()
                conn.close()
                return render_template('modifica_utente.html', error=error, user=user)

            try:
                if password:
                    password_hash = generate_password_hash(password)
                    cursor.execute('''
                        UPDATE users
                        SET filiale=%s, utente=%s, nome_utente=%s, mail=%s, password_hash=%s, campo_libero1=%s, campo_libero2=%s
                        WHERE id=%s
                    ''', (filiale, utente, nome_utente, mail, password_hash, campo1, campo2, id))
                else:
                    cursor.execute('''
                        UPDATE users
                        SET filiale=%s, utente=%s, nome_utente=%s, mail=%s, campo_libero1=%s, campo_libero2=%s
                        WHERE id=%s
                    ''', (filiale, utente, nome_utente, mail, campo1, campo2, id))
                conn.commit()
                cursor.close()
                conn.close()
                return redirect(url_for('lista_utenti'))
            except psycopg2.IntegrityError:
                conn.rollback()
                error = "Errore: la mail risulta già presente."
                cursor.execute('SELECT * FROM users WHERE id = %s', (id,))
                user = cursor.fetchone()
                cursor.close()
                conn.close()
                return render_template('modifica_utente.html', error=error, user=user)
            except Exception as e:
                if conn:
                    conn.rollback()
                print(f"Errore aggiornamento utente: {e}")
                return f"Errore: {e}", 500

        # GET: mostra il form con i dati
        cursor.execute('SELECT * FROM users WHERE id = %s', (id,))
        user = cursor.fetchone()
        cursor.close()
        conn.close()
        if not user:
            return redirect(url_for('lista_utenti'))
        return render_template('modifica_utente.html', user=user)
    except Exception as e:
        print(f"Errore nella modifica utente: {e}")
        return f"Errore: {e}", 500

@app.route('/elimina-utente/<int:id>')
def elimina_utente(id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('DELETE FROM users WHERE id = %s', (id,))
        conn.commit()
        cursor.close()
        conn.close()
        return redirect(url_for('lista_utenti'))
    except Exception as e:
        print(f"Errore eliminazione utente: {e}")
        return f"Errore: {e}", 500

# ... (le altre route esistenti come incassa_scontrino, elimina_scontrino, api, diagnose, ecc.)
# Mantengo il resto del file invariato e sotto ci sono le route già definite in precedenza.
# Per brevità non duplico qui tutte le route già presenti; conserva il resto del file così com'è.
if __name__ == '__main__':
    try:
        init_db()
    except Exception as e:
        print(f"Errore nell'inizializzazione: {e}")
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
