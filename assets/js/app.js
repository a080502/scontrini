// Navbar sticky con effetto rimpicciolimento durante lo scroll
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        let lastScrollTop = 0;
        let scrolling = false;
        
        // Aggiungi le classi CSS necessarie
        navbar.classList.add('navbar-transition');
        
        window.addEventListener('scroll', function() {
            if (!scrolling) {
                scrolling = true;
                requestAnimationFrame(function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    if (scrollTop > 50) {
                        navbar.classList.add('navbar-scrolled');
                    } else {
                        navbar.classList.remove('navbar-scrolled');
                    }
                    
                    lastScrollTop = scrollTop;
                    scrolling = false;
                });
            }
        });
    }
});

// Autocomplete dinamico per i nomi degli scontrini
function setupAutocomplete(inputElement) {
    let currentFocus = -1;
    let debounceTimeout;
    
    // Chiude tutte le liste aperte
    function closeAllLists(elmnt) {
        let x = document.getElementsByClassName("autocomplete-items");
        for (let i = 0; i < x.length; i++) {
            if (elmnt != x[i] && elmnt != inputElement) {
                x[i].parentNode.removeChild(x[i]);
            }
        }
    }
    
    // Evidenzia l'elemento attivo
    function addActive(x) {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        if (x[currentFocus]) x[currentFocus].classList.add("autocomplete-active");
    }
    
    function removeActive(x) {
        for (let i = 0; i < x.length; i++) {
            x[i].classList.remove("autocomplete-active");
        }
    }
    
    // Crea la lista dei suggerimenti
    function createSuggestionsList(suggestions) {
        closeAllLists();
        currentFocus = -1;
        
        if (!suggestions || suggestions.length === 0) return;
        
        const a = document.createElement("div");
        a.setAttribute("id", inputElement.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        inputElement.parentNode.appendChild(a);
        
        suggestions.forEach((suggestion, index) => {
            const b = document.createElement("div");
            const query = inputElement.value;
            const value = suggestion.value;
            const label = suggestion.label || value;
            
            // Evidenzia il testo corrispondente
            const matchIndex = value.toLowerCase().indexOf(query.toLowerCase());
            if (matchIndex > -1) {
                const before = value.substring(0, matchIndex);
                const match = value.substring(matchIndex, matchIndex + query.length);
                const after = value.substring(matchIndex + query.length);
                
                b.innerHTML = `${before}<strong>${match}</strong>${after}`;
                if (suggestion.count) {
                    b.innerHTML += ` <small class="text-muted">(${suggestion.count} volte)</small>`;
                }
            } else {
                b.innerHTML = label;
            }
            
            b.innerHTML += `<input type='hidden' value='${value}'>`;
            
            // Gestione click
            b.addEventListener("click", function(e) {
                inputElement.value = this.getElementsByTagName("input")[0].value;
                closeAllLists();
                inputElement.focus();
            });
            
            a.appendChild(b);
        });
    }
    
    // Fetch dei suggerimenti dall'API
    function fetchSuggestions(query) {
        const url = query ? `api/nomi-scontrini.php?q=${encodeURIComponent(query)}` : 'api/nomi-scontrini.php';
        
        console.log('Chiamata API autocomplete:', url);
        
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    // Per errori 500, proviamo a leggere il contenuto per vedere l'errore PHP
                    return response.text().then(text => {
                        console.error('Errore 500 - Contenuto risposta:', text);
                        throw new Error(`HTTP error! status: ${response.status} - Content: ${text.substring(0, 200)}...`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Dati ricevuti dall\'API:', data);
                if (data.success && data.nomi) {
                    createSuggestionsList(data.nomi);
                } else {
                    console.log('Nessun dato valido ricevuto:', data);
                }
            })
            .catch(error => {
                console.error('Errore caricamento suggerimenti:', error);
                // Mostra un messaggio di errore temporaneo
                const debugDiv = document.createElement('div');
                debugDiv.style.cssText = 'position:fixed;top:10px;right:10px;background:red;color:white;padding:10px;z-index:9999;border-radius:5px;';
                debugDiv.textContent = 'Errore autocomplete: ' + error.message;
                document.body.appendChild(debugDiv);
                setTimeout(() => debugDiv.remove(), 5000);
            });
    }
    
    // Event listener per input
    inputElement.addEventListener("input", function(e) {
        const val = this.value;
        console.log('Input digitato:', val, 'lunghezza:', val.length);
        
        // Debounce per evitare troppe chiamate API
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            if (val.length >= 2) {
                console.log('Ricerca per:', val);
                fetchSuggestions(val);
            } else if (val.length === 0) {
                console.log('Campo vuoto, carico suggerimenti popolari');
                fetchSuggestions(); // Carica suggerimenti più popolari
            } else {
                console.log('Testo troppo corto, chiudo suggerimenti');
                closeAllLists();
            }
        }, 300);
    });
    
    // Focus: mostra suggerimenti più popolari
    inputElement.addEventListener("focus", function(e) {
        console.log('Focus su campo nome, valore attuale:', this.value);
        if (this.value.length === 0) {
            console.log('Caricamento suggerimenti più popolari...');
            fetchSuggestions(); // Carica suggerimenti più popolari
        }
    });
    
    // Gestione tasti freccia e invio
    inputElement.addEventListener("keydown", function(e) {
        let x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        
        if (e.keyCode == 40) { // Freccia giù
            e.preventDefault();
            currentFocus++;
            addActive(x);
        } else if (e.keyCode == 38) { // Freccia su  
            e.preventDefault();
            currentFocus--;
            addActive(x);
        } else if (e.keyCode == 13) { // Invio
            e.preventDefault();
            if (currentFocus > -1 && x) {
                x[currentFocus].click();
            }
        } else if (e.keyCode == 27) { // Escape
            closeAllLists();
        }
    });
    
    // Chiudi liste quando si clicca altrove
    document.addEventListener("click", function (e) {
        closeAllLists(e.target);
    });
}

// Inizializza autocomplete quando la pagina è caricata
document.addEventListener('DOMContentLoaded', function() {
    const nomeInput = document.getElementById('nome');
    if (nomeInput) {
        setupAutocomplete(nomeInput);
        console.log('✅ Autocomplete dinamico inizializzato per elemento:', nomeInput);
        
        // Test immediato per vedere se l'API è raggiungibile
        setTimeout(() => {
            console.log('Test API autocomplete...');
            fetch('api/nomi-scontrini.php')
                .then(response => response.json())
                .then(data => console.log('✅ Test API completato:', data))
                .catch(error => console.error('❌ Test API fallito:', error));
        }, 1000);
    } else {
        console.log('❌ Elemento "nome" non trovato nella pagina');
    }
    
    // Formattazione automatica importi - FIX COMPLETO
    const lordo = document.getElementById('lordo');
    const daVersare = document.getElementById('da_versare');
    
    if (lordo) {
        lordo.addEventListener('blur', function() {
            let value = this.value.replace(',', '.');
            // Solo se c'è un numero valido maggiore di 0
            if (!isNaN(value) && value !== '' && parseFloat(value) > 0) {
                // Formatta il numero
                this.value = parseFloat(value).toFixed(2).replace('.', ',');
                
                // Auto-riempi da_versare se è vuoto
                if (daVersare && daVersare.value === '') {
                    daVersare.value = this.value;
                }
            } else if (value !== '') {
                // Se c'è testo ma non è un numero valido, avvisa ma non cancellare
                console.log('Valore non valido inserito:', value);
            }
            // NON FARE NULLA se il campo è vuoto - lascia che l'utente inserisca il valore
        });
        
        // Suggerimento per auto-riempimento
        lordo.addEventListener('input', function() {
            if (daVersare && daVersare.value === '' && this.value !== '') {
                daVersare.placeholder = 'Premi Tab per copiare ' + this.value;
            }
        });
    }
    
    // Formattazione campo da_versare
    if (daVersare) {
        daVersare.addEventListener('blur', function() {
            let value = this.value.replace(',', '.');
            // Solo se c'è un numero valido (può essere 0 per da_versare)
            if (!isNaN(value) && value !== '' && parseFloat(value) >= 0) {
                this.value = parseFloat(value).toFixed(2).replace('.', ',');
            } else if (value !== '') {
                // Se c'è testo ma non è un numero valido, avvisa ma non cancellare
                console.log('Valore non valido inserito:', value);
            }
            // NON FARE NULLA se il campo è vuoto
        });
    }
});

// Conferma eliminazione
function confermaEliminazione(messaggio) {
    return confirm(messaggio || 'Sei sicuro di voler eliminare questo elemento?');
}

// Filtri per le tabelle
function filtroTabella(filtro) {
    const tabella = document.querySelector('table tbody');
    if (!tabella) return;
    
    const righe = tabella.querySelectorAll('tr');
    
    righe.forEach(riga => {
        switch(filtro) {
            case 'tutti':
                riga.style.display = '';
                break;
            case 'da_incassare':
                const incassato = riga.querySelector('.badge-success');
                riga.style.display = incassato ? 'none' : '';
                break;
            case 'incassati':
                const daIncassare = riga.querySelector('.badge-warning');
                riga.style.display = daIncassare ? 'none' : '';
                break;
        }
    });
    
    // Aggiorna pulsanti attivi
    document.querySelectorAll('.filtri .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[onclick="filtroTabella('${filtro}')"]`).classList.add('active');
}

// Utilità varie
function formatCurrency(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT');
}