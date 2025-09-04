from gestione_scontrini import app, init_db

# Inizializza il database fuori dal blocco if
# Gunicorn eseguirà questo codice all'avvio
try:
    init_db()
    print("Database inizializzato con successo!")
except Exception as e:
    print(f"Errore nell'inizializzazione del database: {e}")

# Non usare app.run(), lascia che sia gunicorn ad avviare l'applicazione
# Gunicorn cercherà l'oggetto 'app' in questo modulo
