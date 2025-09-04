from flask import Flask, render_template, request, redirect, url_for, jsonify, flash
import psycopg2
import psycopg2.extras
from datetime import datetime
import os
import urllib.parse
from contextlib import contextmanager
import logging
from decimal import Decimal, InvalidOperation

# Configurazione logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'default-dev-key')

# Configurazione database - Render PostgreSQL
DATABASE_URL = os.environ.get('DATABASE_URL')

@contextmanager
def get_db_connection():
    """Context manager per connessioni database con pulizia automatica"""
    if not DATABASE_URL:
        raise ValueError("DATABASE_URL non configurata nelle variabili d'ambiente")
    
    conn = None
    try:
        # Parse dell'URL del database per psycopg2
        url = urllib.parse.urlparse(DATABASE_URL)
        
        conn = psycopg2.connect(
            host=url.hostname,
            port=url.port,
            database=url.path[1:],  # Rimuove il '/' iniziale
            user=url.username,
            password=url.password,
            cursor_factory=psycopg2.extras.RealDictCursor,
            sslmode='require'  # Render richiede SSL
        )
        
        yield conn
        
    except Exception as e:
        logger.error(f"Errore connessione database: {e}")
        if conn:
            conn.rollback()
        raise
    finally:
        if conn:
            conn.close()

def valida_input_decimale(valore, nome_campo):
    """Valida e converte input stringa in Decimal"""
    try:
        valore_decimale = Decimal(str(valore).replace(',', '.'))
        if valore_decimale < 0:
            raise ValueError(f"{nome_campo} non può essere negativo")
        return valore_decimale
    except (InvalidOperation, ValueError) as e:
        raise ValueError(f"Valore non valido per {nome_campo}: {valore}")

def init_db():
    """Inizializza il database PostgreSQL"""
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            logger.info("Inizializzazione database...")
            
            # Crea la tabella se non esiste con vincoli di integrità
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS scontrini (
                    id SERIAL PRIMARY KEY,
                    data_scontrino DATE NOT NULL,
                    nome_scontrino TEXT NOT NULL CHECK (LENGTH(nome_scontrino) > 0),
                    importo_versare DECIMAL(10,2) NOT NULL CHECK (importo_versare >= 0),
                    importo_incassare DECIMAL(10,2) NOT NULL CHECK (importo_incassare >= 0),
                    incassato BOOLEAN DEFAULT FALSE,
                    data_incasso TIMESTAMP NULL,
                    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ''')
            
            # Verifica se le colonne esistono
            cursor.execute("""
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'scontrini' AND table_schema = 'public'
            """)
            colonne = [riga['column_name'] for riga in cursor.fetchall()]
            
            # Aggiungi colonne se non esistono
            if 'incassato' not in colonne:
                cursor.execute('ALTER TABLE scontrini ADD COLUMN incassato BOOLEAN DEFAULT FALSE')
                logger.info("Colonna 'incassato' aggiunta")
                
            if 'data_incasso' not in colonne:
                cursor.execute('ALTER TABLE scontrini ADD COLUMN data_incasso TIMESTAMP NULL')
                logger.info("Colonna 'data_incasso' aggiunta")
                
            conn.commit()
            logger.info("Database inizializzato con successo!")
            
    except Exception as e:
        logger.error(f"Errore nell'inizializzazione del database: {e}")
        raise

@app.errorhandler(404)
def errore_pagina_non_trovata(errore):
    return render_template('errore.html', errore="Pagina non trovata"), 404

@app.errorhandler(500)
def errore_interno(errore):
    return render_template('errore.html', errore="Errore interno del server"), 500

@app.route('/')
def index():
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            cursor.execute('SELECT * FROM scontrini')
            scontrini = cursor.fetchall()
            
            cursor.execute('SELECT * FROM scontrini ORDER BY data_inserimento DESC LIMIT 3')
            ultimi_scontrini = cursor.fetchall()
            
            # Calcola i totali usando Decimal per precisione
            totale_versare = sum(Decimal(str(s['importo_versare'])) for s in scontrini)
            totale_incassare = sum(Decimal(str(s['importo_incassare'])) for s in scontrini)
            totale_incassato = sum(Decimal(str(s['importo_incassare'])) for s in scontrini if s['incassato'])
            totale_da_incassare = sum(Decimal(str(s['importo_incassare'])) for s in scontrini if not s['incassato'])
            
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
        logger.error(f"Errore nella homepage: {e}")
        flash(f"Errore nel caricamento della dashboard: {str(e)}", 'error')
        return render_template('dashboard.html', 
                             ultimi_scontrini=[],
                             totale_versare=0, totale_incassare=0,
                             totale_incassato=0, totale_da_incassare=0,
                             num_scontrini=0, num_incassati=0, num_da_incassare=0)

@app.route('/lista')
def lista_scontrini():
    try:
        filtro = request.args.get('filtro', 'tutti')
        pagina = int(request.args.get('pagina', 1))
        per_pagina = 20  # Numero di elementi per pagina
        offset = (pagina - 1) * per_pagina
        
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            # Costruisci query in base al filtro
            query_base = 'SELECT * FROM scontrini'
            query_count = 'SELECT COUNT(*) FROM scontrini'
            
            if filtro == 'incassati':
                clausola_where = ' WHERE incassato = TRUE'
                titolo = "Scontrini Incassati"
            elif filtro == 'da_incassare':
                clausola_where = ' WHERE incassato = FALSE'
                titolo = "Scontrini da Incassare"
            else:
                clausola_where = ''
                titolo = "Tutti gli Scontrini"
            
            clausola_order = ' ORDER BY data_scontrino DESC, data_inserimento DESC'
            clausola_limit = f' LIMIT {per_pagina} OFFSET {offset}'
            
            # Ottieni conteggio totale per paginazione
            cursor.execute(query_count + clausola_where)
            elementi_totali = cursor.fetchone()[0]
            
            # Ottieni risultati paginati
            query = query_base + clausola_where + clausola_order + clausola_limit
            cursor.execute(query)
            scontrini = cursor.fetchall()
            
            # Calcola informazioni paginazione
            pagine_totali = (elementi_totali + per_pagina - 1) // per_pagina
            ha_precedente = pagina > 1
            ha_successiva = pagina < pagine_totali
            
            # Calcola totali per filtro corrente
            cursor.execute(f'SELECT * FROM scontrini{clausola_where}')
            tutti_scontrini_filtrati = cursor.fetchall()
            
            totale_versare = sum(Decimal(str(s['importo_versare'])) for s in tutti_scontrini_filtrati)
            totale_incassare = sum(Decimal(str(s['importo_incassare'])) for s in tutti_scontrini_filtrati)
            totale_incassato = sum(Decimal(str(s['importo_incassare'])) for s in tutti_scontrini_filtrati if s['incassato'])
            totale_da_incassare = sum(Decimal(str(s['importo_incassare'])) for s in tutti_scontrini_filtrati if not s['incassato'])
            
            return render_template('lista.html', 
                                 scontrini=scontrini,
                                 totale_versare=totale_versare,
                                 totale_incassare=totale_incassare,
                                 totale_incassato=totale_incassato,
                                 totale_da_incassare=totale_da_incassare,
                                 filtro=filtro,
                                 titolo=titolo,
                                 pagina=pagina,
                                 pagine_totali=pagine_totali,
                                 ha_precedente=ha_precedente,
                                 ha_successiva=ha_successiva,
                                 elementi_totali=elementi_totali)
                                 
    except Exception as e:
        logger.error(f"Errore nella lista: {e}")
        flash(f"Errore nel caricamento della lista: {str(e)}", 'error')
        return redirect(url_for('index'))

@app.route('/aggiungi', methods=['GET', 'POST'])
def aggiungi_scontrino():
    if request.method == 'POST':
        try:
            data_scontrino = request.form['data_scontrino']
            nome_scontrino = request.form['nome_scontrino'].strip()
            
            # Validazione input
            if not nome_scontrino:
                raise ValueError("Il nome dello scontrino è obbligatorio")
            
            if not data_scontrino:
                raise ValueError("La data dello scontrino è obbligatoria")
            
            importo_versare = valida_input_decimale(request.form['importo_versare'], 'Importo da versare')
            importo_incassare = valida_input_decimale(request.form['importo_incassare'], 'Importo da incassare')
            
            with get_db_connection() as conn:
                cursor = conn.cursor()
                cursor.execute('''
                    INSERT INTO scontrini (data_scontrino, nome_scontrino, importo_versare, importo_incassare) 
                    VALUES (%s, %s, %s, %s)
                ''', (data_scontrino, nome_scontrino, importo_versare, importo_incassare))
                conn.commit()
                
            flash('Scontrino aggiunto con successo!', 'success')
            return redirect(url_for('lista_scontrini'))
            
        except ValueError as e:
            flash(str(e), 'error')
        except Exception as e:
            logger.error(f"Errore nell'aggiunta: {e}")
            flash(f"Errore nell'aggiunta dello scontrino: {str(e)}", 'error')
    
    return render_template('aggiungi.html')

@app.route('/modifica/<int:id>', methods=['GET', 'POST'])
def modifica_scontrino(id):
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            if request.method == 'POST':
                data_scontrino = request.form['data_scontrino']
                nome_scontrino = request.form['nome_scontrino'].strip()
                
                # Validazione input
                if not nome_scontrino:
                    raise ValueError("Il nome dello scontrino è obbligatorio")
                
                if not data_scontrino:
                    raise ValueError("La data dello scontrino è obbligatoria")
                
                importo_versare = valida_input_decimale(request.form['importo_versare'], 'Importo da versare')
                importo_incassare = valida_input_decimale(request.form['importo_incassare'], 'Importo da incassare')
                
                cursor.execute('''
                    UPDATE scontrini 
                    SET data_scontrino=%s, nome_scontrino=%s, importo_versare=%s, importo_incassare=%s 
                    WHERE id=%s
                ''', (data_scontrino, nome_scontrino, importo_versare, importo_incassare, id))
                conn.commit()
                
                flash('Scontrino modificato con successo!', 'success')
                return redirect(url_for('lista_scontrini'))
            
            # GET - mostra form di modifica
            cursor.execute('SELECT * FROM scontrini WHERE id = %s', (id,))
            scontrino = cursor.fetchone()
            
            if scontrino is None:
                flash('Scontrino non trovato!', 'error')
                return redirect(url_for('lista_scontrini'))
            
            return render_template('modifica.html', scontrino=scontrino)
            
    except ValueError as e:
        flash(str(e), 'error')
        return redirect(url_for('modifica_scontrino', id=id))
    except Exception as e:
        logger.error(f"Errore nella modifica: {e}")
        flash(f"Errore nella modifica dello scontrino: {str(e)}", 'error')
        return redirect(url_for('lista_scontrini'))

@app.route('/incassa/<int:id>')
def incassa_scontrino(id):
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            # Verifica che lo scontrino esista e non sia già incassato
            cursor.execute('SELECT incassato FROM scontrini WHERE id = %s', (id,))
            risultato = cursor.fetchone()
            
            if risultato is None:
                flash('Scontrino non trovato!', 'error')
                return redirect(url_for('lista_scontrini'))
                
            if risultato['incassato']:
                flash('Scontrino già incassato!', 'warning')
                return redirect(url_for('lista_scontrini'))
            
            cursor.execute('UPDATE scontrini SET incassato = TRUE, data_incasso = CURRENT_TIMESTAMP WHERE id = %s', (id,))
            conn.commit()
            
            flash('Scontrino incassato con successo!', 'success')
            
    except Exception as e:
        logger.error(f"Errore nell'incasso: {e}")
        flash(f"Errore nell'incasso dello scontrino: {str(e)}", 'error')
        
    return redirect(url_for('lista_scontrini'))

@app.route('/annulla_incasso/<int:id>')
def annulla_incasso(id):
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            # Verifica che lo scontrino esista e sia incassato
            cursor.execute('SELECT incassato FROM scontrini WHERE id = %s', (id,))
            risultato = cursor.fetchone()
            
            if risultato is None:
                flash('Scontrino non trovato!', 'error')
                return redirect(url_for('lista_scontrini'))
                
            if not risultato['incassato']:
                flash('Scontrino non è incassato!', 'warning')
                return redirect(url_for('lista_scontrini'))
            
            cursor.execute('UPDATE scontrini SET incassato = FALSE, data_incasso = NULL WHERE id = %s', (id,))
            conn.commit()
            
            flash('Incasso annullato con successo!', 'success')
            
    except Exception as e:
        logger.error(f"Errore nell'annullamento incasso: {e}")
        flash(f"Errore nell'annullamento dell'incasso: {str(e)}", 'error')
        
    return redirect(url_for('lista_scontrini'))

@app.route('/elimina/<int:id>')
def elimina_scontrino(id):
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            
            # Verifica che lo scontrino esista
            cursor.execute('SELECT COUNT(*) FROM scontrini WHERE id = %s', (id,))
            if cursor.fetchone()[0] == 0:
                flash('Scontrino non trovato!', 'error')
                return redirect(url_for('lista_scontrini'))
            
            cursor.execute('DELETE FROM scontrini WHERE id = %s', (id,))
            conn.commit()
            
            flash('Scontrino eliminato con successo!', 'success')
            
    except Exception as e:
        logger.error(f"Errore nell'eliminazione: {e}")
        flash(f"Errore nell'eliminazione dello scontrino: {str(e)}", 'error')
        
    return redirect(url_for('lista_scontrini'))

@app.route('/api/scontrini')
def api_scontrini():
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            cursor.execute('SELECT * FROM scontrini ORDER BY data_scontrino DESC')
            scontrini = cursor.fetchall()
            
            # Converte Decimal in float per JSON serializzazione
            scontrini_json = []
            for s in scontrini:
                scontrino_dict = dict(s)
                scontrino_dict['importo_versare'] = float(scontrino_dict['importo_versare'])
                scontrino_dict['importo_incassare'] = float(scontrino_dict['importo_incassare'])
                scontrini_json.append(scontrino_dict)
                
            return jsonify(scontrini_json)
            
    except Exception as e:
        logger.error(f"Errore nell'API: {e}")
        return jsonify({"errore": str(e)}), 500

@app.route('/api/statistiche')
def api_statistiche():
    """Endpoint API per statistiche dashboard"""
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            cursor.execute('SELECT * FROM scontrini')
            scontrini = cursor.fetchall()
            
            statistiche = {
                'totale_scontrini': len(scontrini),
                'scontrini_incassati': sum(1 for s in scontrini if s['incassato']),
                'scontrini_da_incassare': sum(1 for s in scontrini if not s['incassato']),
                'scontrini_versati': sum(1 for s in scontrini if s['versato']),
                'scontrini_da_versare': sum(1 for s in scontrini if not s['versato']),
                'scontrini_completi': sum(1 for s in scontrini if s['incassato'] and s['versato']),
                'totale_versare': float(sum(Decimal(str(s['importo_versare'])) for s in scontrini)),
                'totale_incassare': float(sum(Decimal(str(s['importo_incassare'])) for s in scontrini)),
                'totale_incassato': float(sum(Decimal(str(s['importo_incassare'])) for s in scontrini if s['incassato'])),
                'totale_da_incassare': float(sum(Decimal(str(s['importo_incassare'])) for s in scontrini if not s['incassato'])),
                'totale_versato': float(sum(Decimal(str(s['importo_versare'])) for s in scontrini if s['versato'])),
                'totale_da_versare': float(sum(Decimal(str(s['importo_versare'])) for s in scontrini if not s['versato'])),
                'differenza_incasso_versamento': float(sum(Decimal(str(s['importo_incassare'])) for s in scontrini if s['incassato']) - sum(Decimal(str(s['importo_versare'])) for s in scontrini if s['versato']))
            }
            
            return jsonify(statistiche)
            
    except Exception as e:
        logger.error(f"Errore nell'API statistiche: {e}")
        return jsonify({"errore": str(e)}), 500

# Route di test
@app.route('/test')
def test():
    return "Flask funziona correttamente!"

@app.route('/test-db')
def test_db():
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            cursor.execute('SELECT version();')
            versione = cursor.fetchone()
            return f"Database connesso! Versione PostgreSQL: {versione}"
    except Exception as e:
        return f"Errore connessione database: {e}"

@app.route('/salute')
def controllo_salute():
    """Health check endpoint per monitoring"""
    try:
        with get_db_connection() as conn:
            cursor = conn.cursor()
            cursor.execute('SELECT 1;')
            cursor.fetchone()
            
        return jsonify({
            "stato": "ok",
            "timestamp": datetime.now().isoformat(),
            "database": "connesso"
        }), 200
        
    except Exception as e:
        logger.error(f"Health check fallito: {e}")
        return jsonify({
            "stato": "errore",
            "timestamp": datetime.now().isoformat(),
            "database": "disconnesso",
            "errore": str(e)
        }), 503

if __name__ == '__main__':
    # Inizializza il database all'avvio
    try:
        init_db()
        logger.info("App inizializzata con successo")
    except Exception as e:
        logger.error(f"Errore nell'inizializzazione: {e}")
        exit(1)
    
    # Avvia l'app
    porta = int(os.environ.get('PORT', 5000))
    debug_mode = os.environ.get('FLASK_ENV') == 'development'
    
    app.run(host='0.0.0.0', port=porta, debug=debug_mode)
