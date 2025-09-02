from gestione_scontrini import app, init_db, create_templates

# Inizializza l'applicazione
create_templates()
init_db()

if __name__ == "__main__":
    app.run()