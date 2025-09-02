from gestione_scontrini import app, init_db, create_templates

# Inizializza template e database all'avvio su Render
def create_templates():
    import os
    templates = {
        'base.html': open('templates/base.html', 'r', encoding='utf-8').read(),
        'dashboard.html': open('templates/dashboard.html', 'r', encoding='utf-8').read(),
        'lista.html': open('templates/lista.html', 'r', encoding='utf-8').read(),
        'aggiungi.html': open('templates/aggiungi.html', 'r', encoding='utf-8').read(),
        'modifica.html': open('templates/modifica.html', 'r', encoding='utf-8').read(),
    }
    if not os.path.exists('templates'):
        os.makedirs('templates')
    for filename, content in templates.items():
        with open(f'templates/{filename}', 'w', encoding='utf-8') as f:
            f.write(content)

create_templates()
init_db()

if __name__ == "__main__":
    app.run()