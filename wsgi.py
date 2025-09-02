from gestione_scontrini import app, init_db

# Inizializza il database all'avvio
init_db()

if __name__ == "__main__":
    app.run()
