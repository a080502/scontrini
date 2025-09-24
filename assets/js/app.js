// Autocomplete per i nomi degli scontrini
function autocomplete(inp, arr) {
    let currentFocus;
    
    inp.addEventListener("input", function(e) {
        let a, b, i, val = this.value;
        closeAllLists();
        if (!val) return false;
        currentFocus = -1;
        
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        this.parentNode.appendChild(a);
        
        for (i = 0; i < arr.length; i++) {
            if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                b = document.createElement("DIV");
                b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
                b.innerHTML += arr[i].substr(val.length);
                b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
                
                b.addEventListener("click", function(e) {
                    inp.value = this.getElementsByTagName("input")[0].value;
                    closeAllLists();
                });
                
                a.appendChild(b);
            }
        }
    });
    
    inp.addEventListener("keydown", function(e) {
        let x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) {
            currentFocus++;
            addActive(x);
        } else if (e.keyCode == 38) {
            currentFocus--;
            addActive(x);
        } else if (e.keyCode == 13) {
            e.preventDefault();
            if (currentFocus > -1) {
                if (x) x[currentFocus].click();
            }
        }
    });
    
    function addActive(x) {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        x[currentFocus].classList.add("autocomplete-active");
    }
    
    function removeActive(x) {
        for (let i = 0; i < x.length; i++) {
            x[i].classList.remove("autocomplete-active");
        }
    }
    
    function closeAllLists(elmnt) {
        let x = document.getElementsByClassName("autocomplete-items");
        for (let i = 0; i < x.length; i++) {
            if (elmnt != x[i] && elmnt != inp) {
                x[i].parentNode.removeChild(x[i]);
            }
        }
    }
    
    document.addEventListener("click", function (e) {
        closeAllLists(e.target);
    });
}

// Inizializza autocomplete quando la pagina è caricata
document.addEventListener('DOMContentLoaded', function() {
    // Carica i nomi degli scontrini per autocomplete
    const nomeInput = document.getElementById('nome');
    if (nomeInput) {
        fetch('api/nomi-scontrini.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.nomi) {
                    autocomplete(nomeInput, data.nomi);
                    console.log('Autocomplete caricato con', data.nomi.length, 'nomi');
                } else {
                    console.log('Nessun nome disponibile per autocomplete');
                }
            })
            .catch(error => {
                console.log('Errore caricamento autocomplete:', error);
                // Fallback: autocomplete con array vuoto
                autocomplete(nomeInput, []);
            });
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