/**
 * Gestione Dettagli Scontrino con Excel Import
 */

class ScontrinoDettagli {
    constructor(scontrinoId, container) {
        this.scontrinoId = scontrinoId;
        this.container = container;
        this.dettagli = [];
        this.initializeEventListeners();
        this.caricaDettagli();
    }
    
    initializeEventListeners() {
        // Aggiungi nuovo dettaglio
        document.getElementById('btn-aggiungi-dettaglio')?.addEventListener('click', () => {
            this.mostraFormDettaglio();
        });
        
        // Import Excel
        document.getElementById('btn-import-excel')?.addEventListener('click', () => {
            this.mostraImportExcel();
        });
        
        // Handle file Excel
        document.getElementById('excel-file')?.addEventListener('change', (e) => {
            this.handleExcelFile(e.target.files[0]);
        });
    }
    
    async caricaDettagli() {
        try {
            const response = await fetch(`api/scontrino-dettagli.php?action=get&scontrino_id=${this.scontrinoId}`);
            const data = await response.json();
            
            if (data.success) {
                this.dettagli = data.dettagli;
                this.renderDettagli();
                this.aggiornaTotali(data.totali);
            } else {
                throw new Error(data.error || 'Errore nel caricamento dettagli');
            }
        } catch (error) {
            console.error('Errore caricamento dettagli:', error);
            this.showAlert('Errore nel caricamento dei dettagli: ' + error.message, 'error');
        }
    }
    
    renderDettagli() {
        const tbody = document.getElementById('dettagli-tbody');
        if (!tbody) return;
        
        if (this.dettagli.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox mb-2 d-block" style="font-size: 2rem;"></i>
                        Nessun dettaglio presente. <br>
                        <small>Aggiungi articoli usando il pulsante "Aggiungi Dettaglio" o importa da Excel.</small>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.dettagli.map(dettaglio => `
            <tr data-id="${dettaglio.id}">
                <td class="text-center">${dettaglio.numero_ordine}</td>
                <td>${dettaglio.codice_articolo || '-'}</td>
                <td>${this.escapeHtml(dettaglio.descrizione_materiale)}</td>
                <td class="text-end">${this.formatNumber(dettaglio.qta)}</td>
                <td class="text-end">€ ${this.formatNumber(dettaglio.prezzo_unitario, 2)}</td>
                <td class="text-end"><strong>€ ${this.formatNumber(dettaglio.prezzo_totale, 2)}</strong></td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" 
                                onclick="scontrinoDettagli.modificaDettaglio(${dettaglio.id})"
                                title="Modifica">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="scontrinoDettagli.eliminaDettaglio(${dettaglio.id})"
                                title="Elimina">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    aggiornaTotali(totali) {
        const elements = {
            'totale-articoli': totali.num_articoli,
            'totale-qta': this.formatNumber(totali.qta_totale),
            'totale-importo': '€ ' + this.formatNumber(totali.totale, 2)
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }
    
    mostraFormDettaglio(dettaglio = null) {
        const modalHtml = `
            <div class="modal fade" id="modal-dettaglio" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                ${dettaglio ? 'Modifica' : 'Aggiungi'} Dettaglio
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="form-dettaglio">
                                <div class="mb-3">
                                    <label class="form-label">Codice Articolo <small class="text-muted">(opzionale)</small></label>
                                    <input type="text" class="form-control" name="codice_articolo" 
                                           value="${dettaglio ? this.escapeHtml(dettaglio.codice_articolo || '') : ''}"
                                           list="codici-articoli">
                                    <datalist id="codici-articoli"></datalist>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Descrizione Materiale <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="descrizione_materiale" 
                                           value="${dettaglio ? this.escapeHtml(dettaglio.descrizione_materiale) : ''}"
                                           required list="descrizioni-materiali">
                                    <datalist id="descrizioni-materiali"></datalist>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Quantità <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="qta" 
                                               value="${dettaglio ? dettaglio.qta : '1'}"
                                               step="0.01" min="0.01" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prezzo Unitario <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="prezzo_unitario" 
                                               value="${dettaglio ? dettaglio.prezzo_unitario : '0.00'}"
                                               step="0.01" min="0" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Prezzo Totale</label>
                                    <input type="text" class="form-control" id="prezzo-totale-calc" readonly 
                                           style="background-color: #f8f9fa;">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                            <button type="button" class="btn btn-primary" onclick="scontrinoDettagli.salvaDettaglio(${dettaglio ? dettaglio.id : 'null'})">
                                ${dettaglio ? 'Aggiorna' : 'Aggiungi'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Rimuovi modal esistente
        document.getElementById('modal-dettaglio')?.remove();
        
        // Aggiungi nuovo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById('modal-dettaglio'));
        modal.show();
        
        // Setup autocomplete e calcolo totale
        this.setupFormHandlers();
    }
    
    setupFormHandlers() {
        const form = document.getElementById('form-dettaglio');
        const qtaInput = form.querySelector('[name="qta"]');
        const prezzoInput = form.querySelector('[name="prezzo_unitario"]');
        const totaleDisplay = document.getElementById('prezzo-totale-calc');
        const codiceInput = form.querySelector('[name="codice_articolo"]');
        const descrizioneInput = form.querySelector('[name="descrizione_materiale"]');
        
        // Calcolo automatico prezzo totale
        const calcolaTotale = () => {
            const qta = parseFloat(qtaInput.value) || 0;
            const prezzo = parseFloat(prezzoInput.value) || 0;
            const totale = qta * prezzo;
            totaleDisplay.value = '€ ' + this.formatNumber(totale, 2);
        };
        
        qtaInput.addEventListener('input', calcolaTotale);
        prezzoInput.addEventListener('input', calcolaTotale);
        calcolaTotale(); // Calcolo iniziale
        
        // Autocomplete per codici e descrizioni
        let searchTimeout;
        
        const setupAutocomplete = (input, datalistId) => {
            input.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (input.value.length >= 2) {
                        this.caricaSuggerimenti(input.value, datalistId);
                    }
                }, 300);
            });
        };
        
        setupAutocomplete(codiceInput, 'codici-articoli');
        setupAutocomplete(descrizioneInput, 'descrizioni-materiali');
        
        // Auto-fill quando si seleziona un codice articolo
        codiceInput.addEventListener('change', () => {
            if (codiceInput.value) {
                this.autoFillArticolo(codiceInput.value);
            }
        });
    }
    
    async caricaSuggerimenti(query, datalistId) {
        try {
            const response = await fetch(`api/scontrino-dettagli.php?action=search_articoli&q=${encodeURIComponent(query)}&limit=10`);
            const data = await response.json();
            
            if (data.success) {
                const datalist = document.getElementById(datalistId);
                if (datalist) {
                    datalist.innerHTML = data.articoli.map(articolo => 
                        `<option value="${this.escapeHtml(articolo.codice_articolo || articolo.descrizione_materiale)}" 
                                data-descrizione="${this.escapeHtml(articolo.descrizione_materiale)}"
                                data-prezzo="${articolo.prezzo_medio}">
                            ${this.escapeHtml(articolo.descrizione_materiale)} (€${this.formatNumber(articolo.prezzo_medio, 2)})
                        </option>`
                    ).join('');
                }
            }
        } catch (error) {
            console.error('Errore caricamento suggerimenti:', error);
        }
    }
    
    autoFillArticolo(codice) {
        // Auto-fill basato sui dati storici
        fetch(`api/scontrino-dettagli.php?action=search_articoli&q=${encodeURIComponent(codice)}&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.articoli.length > 0) {
                    const articolo = data.articoli[0];
                    const form = document.getElementById('form-dettaglio');
                    
                    form.querySelector('[name="descrizione_materiale"]').value = articolo.descrizione_materiale;
                    form.querySelector('[name="prezzo_unitario"]').value = this.formatNumber(articolo.prezzo_medio, 2);
                    
                    // Trigger calcolo totale
                    form.querySelector('[name="prezzo_unitario"]').dispatchEvent(new Event('input'));
                }
            })
            .catch(error => console.error('Errore auto-fill:', error));
    }
    
    async salvaDettaglio(id = null) {
        const form = document.getElementById('form-dettaglio');
        const formData = new FormData(form);
        
        const dettaglio = {
            scontrino_id: this.scontrinoId,
            codice_articolo: formData.get('codice_articolo') || null,
            descrizione_materiale: formData.get('descrizione_materiale'),
            qta: parseFloat(formData.get('qta')),
            prezzo_unitario: parseFloat(formData.get('prezzo_unitario'))
        };
        
        if (id) dettaglio.id = id;
        
        try {
            const action = id ? 'update' : 'add';
            const response = await fetch(`api/scontrino-dettagli.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dettaglio)
            });
            
            const data = await response.json();
            
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modal-dettaglio')).hide();
                
                if (data.dettagli) {
                    this.dettagli = data.dettagli;
                    this.renderDettagli();
                    this.aggiornaTotali(data.totali);
                } else {
                    this.caricaDettagli(); // Ricarica se non abbiamo i dati aggiornati
                }
                
                this.showAlert(data.message, 'success');
            } else {
                throw new Error(data.error || 'Errore nel salvataggio');
            }
        } catch (error) {
            console.error('Errore salvataggio dettaglio:', error);
            this.showAlert('Errore nel salvataggio: ' + error.message, 'error');
        }
    }
    
    modificaDettaglio(id) {
        const dettaglio = this.dettagli.find(d => d.id == id);
        if (dettaglio) {
            this.mostraFormDettaglio(dettaglio);
        }
    }
    
    async eliminaDettaglio(id) {
        if (!confirm('Sei sicuro di voler eliminare questo dettaglio?')) return;
        
        try {
            const response = await fetch(`api/scontrino-dettagli.php?action=delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, scontrino_id: this.scontrinoId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.dettagli = data.dettagli;
                this.renderDettagli();
                this.aggiornaTotali(data.totali);
                this.showAlert(data.message, 'success');
            } else {
                throw new Error(data.error || 'Errore nell\'eliminazione');
            }
        } catch (error) {
            console.error('Errore eliminazione dettaglio:', error);
            this.showAlert('Errore nell\'eliminazione: ' + error.message, 'error');
        }
    }
    
    mostraImportExcel() {
        const modalHtml = `
            <div class="modal fade" id="modal-import-excel" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-file-excel me-2"></i>Importa da Excel
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Formato richiesto:</strong><br>
                                Il file Excel deve avere le seguenti colonne (in questo ordine):<br>
                                <code>Codice Articolo | Descrizione Materiale | Quantità | Prezzo Unitario</code>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Seleziona file Excel (.xlsx, .xls)</label>
                                <input type="file" class="form-control" id="excel-file" 
                                       accept=".xlsx,.xls" required>
                            </div>
                            
                            <div id="excel-preview" class="d-none">
                                <h6>Anteprima dati:</h6>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-striped">
                                        <thead id="excel-headers"></thead>
                                        <tbody id="excel-data"></tbody>
                                    </table>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Righe da importare: <span id="excel-count">0</span></small>
                                </div>
                            </div>
                            
                            <div id="excel-errors" class="d-none mt-3">
                                <div class="alert alert-warning">
                                    <strong>Errori di validazione:</strong>
                                    <ul id="excel-errors-list"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                            <button type="button" class="btn btn-success" id="btn-conferma-import" disabled>
                                <i class="fas fa-upload me-2"></i>Importa Dati
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Rimuovi modal esistente
        document.getElementById('modal-import-excel')?.remove();
        
        // Aggiungi nuovo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById('modal-import-excel'));
        modal.show();
        
        // Event listeners
        document.getElementById('btn-conferma-import').addEventListener('click', () => {
            this.confermaImportExcel();
        });
    }
    
    async handleExcelFile(file) {
        if (!file) return;
        
        try {
            // Carica XLSX library se non presente
            if (typeof XLSX === 'undefined') {
                await this.loadXLSXLibrary();
            }
            
            const data = await this.readExcelFile(file);
            this.showExcelPreview(data);
            
        } catch (error) {
            console.error('Errore lettura Excel:', error);
            this.showAlert('Errore nella lettura del file Excel: ' + error.message, 'error');
        }
    }
    
    loadXLSXLibrary() {
        return new Promise((resolve, reject) => {
            if (typeof XLSX !== 'undefined') {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    readExcelFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                    
                    resolve(jsonData);
                } catch (error) {
                    reject(error);
                }
            };
            
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    }
    
    showExcelPreview(excelData) {
        if (!excelData || excelData.length < 2) {
            this.showAlert('File Excel vuoto o formato non valido', 'error');
            return;
        }
        
        const headers = excelData[0];
        const rows = excelData.slice(1).filter(row => row.some(cell => cell !== null && cell !== ''));
        
        // Normalizza i dati
        const dettagliData = rows.map((row, index) => {
            return {
                riga: index + 2, // +2 perché saltiamo header e iniziamo da 1
                codice_articolo: row[0] || null,
                descrizione_materiale: row[1] || '',
                qta: row[2] || '',
                prezzo_unitario: row[3] || ''
            };
        });
        
        // Validazione
        const errori = [];
        const dettagliValidi = [];
        
        dettagliData.forEach(dettaglio => {
            const erroriRiga = [];
            
            if (!dettaglio.descrizione_materiale.trim()) {
                erroriRiga.push('Descrizione materiale obbligatoria');
            }
            
            if (!dettaglio.qta || isNaN(parseFloat(dettaglio.qta)) || parseFloat(dettaglio.qta) <= 0) {
                erroriRiga.push('Quantità deve essere un numero > 0');
            }
            
            if (!dettaglio.prezzo_unitario || isNaN(parseFloat(dettaglio.prezzo_unitario)) || parseFloat(dettaglio.prezzo_unitario) < 0) {
                erroriRiga.push('Prezzo unitario deve essere un numero >= 0');
            }
            
            if (erroriRiga.length > 0) {
                errori.push(`Riga ${dettaglio.riga}: ${erroriRiga.join(', ')}`);
            } else {
                dettagliValidi.push({
                    codice_articolo: dettaglio.codice_articolo,
                    descrizione_materiale: dettaglio.descrizione_materiale.trim(),
                    qta: parseFloat(dettaglio.qta),
                    prezzo_unitario: parseFloat(dettaglio.prezzo_unitario)
                });
            }
        });
        
        // Mostra anteprima
        const preview = document.getElementById('excel-preview');
        const headersEl = document.getElementById('excel-headers');
        const dataEl = document.getElementById('excel-data');
        const countEl = document.getElementById('excel-count');
        const errorsEl = document.getElementById('excel-errors');
        const errorsListEl = document.getElementById('excel-errors-list');
        const btnConfirm = document.getElementById('btn-conferma-import');
        
        // Headers
        headersEl.innerHTML = `
            <tr>
                <th>Codice Art.</th>
                <th>Descrizione</th>
                <th>Quantità</th>
                <th>Prezzo Unit.</th>
                <th>Totale</th>
            </tr>
        `;
        
        // Data
        dataEl.innerHTML = dettagliValidi.slice(0, 10).map(dettaglio => `
            <tr>
                <td>${this.escapeHtml(dettaglio.codice_articolo || '-')}</td>
                <td>${this.escapeHtml(dettaglio.descrizione_materiale)}</td>
                <td class="text-end">${this.formatNumber(dettaglio.qta)}</td>
                <td class="text-end">€ ${this.formatNumber(dettaglio.prezzo_unitario, 2)}</td>
                <td class="text-end">€ ${this.formatNumber(dettaglio.qta * dettaglio.prezzo_unitario, 2)}</td>
            </tr>
        `).join('');
        
        if (dettagliValidi.length > 10) {
            dataEl.innerHTML += `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        ... e altri ${dettagliValidi.length - 10} articoli
                    </td>
                </tr>
            `;
        }
        
        countEl.textContent = dettagliValidi.length;
        
        // Errori
        if (errori.length > 0) {
            errorsListEl.innerHTML = errori.map(errore => `<li>${errore}</li>`).join('');
            errorsEl.classList.remove('d-none');
        } else {
            errorsEl.classList.add('d-none');
        }
        
        // Salva dati per importazione
        this.excelDataToImport = dettagliValidi;
        
        preview.classList.remove('d-none');
        btnConfirm.disabled = dettagliValidi.length === 0;
    }
    
    async confermaImportExcel() {
        if (!this.excelDataToImport || this.excelDataToImport.length === 0) {
            this.showAlert('Nessun dato da importare', 'error');
            return;
        }
        
        try {
            const response = await fetch(`api/scontrino-dettagli.php?action=import_excel`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    scontrino_id: this.scontrinoId,
                    dettagli: this.excelDataToImport
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modal-import-excel')).hide();
                
                this.dettagli = data.dettagli;
                this.renderDettagli();
                this.aggiornaTotali(data.totali);
                
                // Mostra risultato dettagliato
                const risultato = data.risultato;
                let message = `Importazione completata:\n`;
                message += `• ${risultato.successi} articoli importati\n`;
                if (risultato.errori.length > 0) {
                    message += `• ${risultato.errori.length} errori\n\n`;
                    message += `Errori:\n${risultato.errori.slice(0, 5).join('\n')}`;
                    if (risultato.errori.length > 5) {
                        message += `\n... e altri ${risultato.errori.length - 5} errori`;
                    }
                }
                
                this.showAlert(message, risultato.errori.length > 0 ? 'warning' : 'success');
            } else {
                throw new Error(data.error || 'Errore nell\'importazione');
            }
        } catch (error) {
            console.error('Errore importazione Excel:', error);
            this.showAlert('Errore nell\'importazione: ' + error.message, 'error');
        }
    }
    
    // Utility functions
    formatNumber(number, decimals = 0) {
        return new Intl.NumberFormat('it-IT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showAlert(message, type = 'info') {
        // Usa il sistema di alert esistente o implementa uno nuovo
        if (typeof showAlert === 'function') {
            showAlert(message, type);
        } else {
            // Fallback semplice
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${this.escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            const container = document.querySelector('.container') || document.body;
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-remove dopo 5 secondi
            setTimeout(() => {
                document.querySelector('.alert')?.remove();
            }, 5000);
        }
    }
}

// Variabile globale per accesso da onclick
let scontrinoDettagli;