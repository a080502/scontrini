from flask import Flask
import sqlite3

app = Flask(__name__)

# Configuration for SQLite database
DATABASE = 'development.db'

def get_db():
    conn = sqlite3.connect(DATABASE)
    return conn

@app.route('/')
def home():
    return 'Hello, Flask with SQLite!'

if __name__ == '__main__':
    app.run(debug=True)