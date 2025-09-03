from gestione_scontrini import app, init_db

try:
    init_db()
    print("Database inizializzato con successo!")
except Exception as e:
    print(f"Errore nell'inizializzazione del database: {e}")

if __name__ == "__main__":
    app.run(debug=True)
