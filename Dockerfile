# Scegli un'immagine di base di Python
FROM python:3.11-slim

# Imposta la directory di lavoro nel container
WORKDIR /app

# Installa le dipendenze di sistema necessarie
RUN apt-get update && apt-get install -y \
    build-essential \
    libpq-dev

# Copia i file del tuo progetto nel container
COPY . .

# Installa le dipendenze Python
RUN pip install --no-cache-dir -r requirements.txt

# Definisci il comando per avviare l'applicazione
CMD gunicorn wsgi:app
