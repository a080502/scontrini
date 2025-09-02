# ... (mantieni tutto il codice esistente fino alla configurazione del database)

# Modifica la configurazione del database per usare un percorso assoluto
import os

# Ottieni il percorso della directory dell'applicazione
APP_DIR = os.path.dirname(os.path.abspath(__file__))
# Configura il percorso del database
DATABASE = os.path.join(APP_DIR, 'scontrini.db')

# ... (mantieni tutto il resto del codice invariato fino alla fine, ma rimuovi o commenta il blocco if __name__ == '__main__':)