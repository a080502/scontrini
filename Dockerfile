# Usa un'immagine Python ufficiale come base
FROM python:3.11-slim

# Imposta la directory di lavoro nel container
WORKDIR /app

# Installa le dipendenze di sistema (come libpq-dev per psycopg2)
# La riga `RUN apt-get update && apt-get install -y libpq-dev` 
# potrebbe essere necessaria a seconda della tua app.
RUN apt-get update && apt-get install -y libpq-dev

# Copia i file del tuo progetto nel container
COPY . .

# Installa le dipendenze Python, incluso gunicorn
RUN pip install --no-cache-dir -r requirements.txt

# Espone la porta che gunicorn ascolterà
EXPOSE 8000

# Definisce il comando di avvio
CMD ["gunicorn", "wsgi:app"]
