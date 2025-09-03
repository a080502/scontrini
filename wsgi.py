from gestione_scontrini import app, init_db

# Inizializza il database all'avvio
try:
    init_db()
    print("Database inizializzato con successo!")
except Exception as e:
    print(f"Errore nell'inizializzazione del database: {e}")

if __name__ == "__main__":
    # Per sviluppo locale
    app.run(debug=True)
else:
    # Per produzione su Render
    init_db()