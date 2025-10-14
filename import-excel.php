<?php
require_once 'includes/installation_check.php';
requireBootstrap();
Auth::requireLogin();

// Verifica autorizzazioni - Solo admin e responsabili possono importare Excel
if (!Auth::isAdmin() && !Auth::isResponsabile()) {
    Utils::redirect('index.php?error=' . urlencode('Non hai i permessi per accedere a questa funzionalità'));
}

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();
$error = '';
$success = '';
$risultati_importazione = null;

// Gestione risultati da URL (dopo redirect da API)
if (isset($_GET['success']) && isset($_GET['risultati'])) {
    $risultati_importazione = json_decode(urldecode($_GET['risultati']), true);
    $success = 'Importazione completata con successo!';
}

// Per responsabili e admin, recupera la lista utenti disponibili
$available_users = Auth::getAvailableUsersForReceipts();

$title = "Importazione Massiva da Excel";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-file-excel text-success me-2"></i><?php echo $title; ?></h1>
    <div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Torna alla Lista
        </a>
        <a href="aggiungi.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Aggiungi Singolo
        </a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if ($risultati_importazione): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Risultati Importazione</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <h3 class="text-success"><?php echo $risultati_importazione['scontrini_creati']; ?></h3>
                    <small class="text-muted">Scontrini Creati</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h3 class="text-primary"><?php echo $risultati_importazione['dettagli_creati']; ?></h3>
                    <small class="text-muted">Articoli Importati</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h3 class="text-warning"><?php echo count($risultati_importazione['errori']); ?></h3>
                    <small class="text-muted">Errori</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h3 class="text-info">€ <?php echo number_format($risultati_importazione['totale_importo'], 2, ',', '.'); ?></h3>
                    <small class="text-muted">Totale Importato</small>
                </div>
            </div>
        </div>
        
        <?php if (!empty($risultati_importazione['errori'])): ?>
        <div class="mt-3">
            <h6>Errori Riscontrati:</h6>
            <div class="alert alert-warning">
                <ul class="mb-0">
                    <?php foreach (array_slice($risultati_importazione['errori'], 0, 10) as $errore): ?>
                    <li><?php echo htmlspecialchars($errore); ?></li>
                    <?php endforeach; ?>
                    <?php if (count($risultati_importazione['errori']) > 10): ?>
                    <li><em>... e altri <?php echo count($risultati_importazione['errori']) - 10; ?> errori</em></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($risultati_importazione['scontrini_ids'])): ?>
        <div class="mt-3">
            <h6>Scontrini Creati:</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (array_slice($risultati_importazione['scontrini_ids'], 0, 20) as $id => $numero): ?>
                <a href="modifica.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                    <?php echo htmlspecialchars($numero); ?>
                </a>
                <?php endforeach; ?>
                <?php if (count($risultati_importazione['scontrini_ids']) > 20): ?>
                <span class="badge bg-secondary">... e altri <?php echo count($risultati_importazione['scontrini_ids']) - 20; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Carica File Excel</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>Formato File Excel Richiesto:</h6>
            <p class="mb-2">Il file Excel deve contenere le seguenti colonne nell'ordine indicato:</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Colonna A</th>
                            <th>Colonna B</th>
                            <th>Colonna C</th>
                            <th>Colonna D</th>
                            <th>Colonna E</th>
                            <th>Colonna F</th>
                            <th>Colonna G</th>
                            <th>Colonna H</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Numero D'Ordine</strong><br><small class="text-muted">Es: ORD001</small></td>
                            <td><strong>Nome Scontrino</strong><br><small class="text-muted">Es: Materiali Ufficio (opzionale)</small></td>
                            <td><strong>Data Scontrino</strong><br><small class="text-muted">Es: 15/01/2024, 6/10/25, 10/6/25</small></td>
                            <td><strong>Codice Articolo</strong><br><small class="text-muted">Es: ART001 (opzionale)</small></td>
                            <td><strong>Descrizione</strong><br><small class="text-muted">Es: Penne biro blu</small></td>
                            <td><strong>Quantità</strong><br><small class="text-muted">Es: 10</small></td>
                            <td><strong>Prezzo Unitario (senza IVA)</strong><br><small class="text-muted">Es: 1.50</small></td>
                            <td><strong>Prezzo Totale (senza IVA)</strong><br><small class="text-muted">Es: 15.00</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <strong>Note Importanti:</strong>
                <ul class="mb-0">
                    <li>Il <strong>Numero D'Ordine</strong> raggruppa gli articoli nello stesso scontrino</li>
                    <li>Il <strong>Nome Scontrino</strong> è opzionale; se vuoto, verrà impostato automaticamente come "SCONTRINO SENZA NOME !! AGGIORNARE !!"</li>
                    <li>La <strong>Data Scontrino</strong> supporta vari formati: DD/MM/YYYY, MM/DD/YY, DD/MM/YY</li>
                    <li>Il <strong>Codice Articolo</strong> è opzionale, può essere vuoto</li>
                    <li>Il <strong>Prezzo Unitario</strong> può essere anche negativo per rappresentare sconti o storni</li>
                    <li>I prezzi nel file sono <strong>senza IVA</strong>; il sistema calcolerà automaticamente l'importo con IVA al 22%</li>
                    <li>Il sistema calcolerà automaticamente il totale dello scontrino sommando tutti gli articoli</li>
                    <li>Le righe con meno di 3 valori vengono automaticamente scartate</li>
                </ul>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="form-import">
            <?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user me-2"></i>Associa Scontrini a Utente</label>
                <select name="utente_id" class="form-select">
                    <option value="">-- Associa a te stesso --</option>
                    <?php foreach ($available_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['nome'] . ' (' . $user['username'] . ')'); ?>
                            <?php if (Auth::isAdmin()): ?>
                                - <?php echo htmlspecialchars($user['filiale_nome']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-file-excel me-2"></i>File Excel (.xlsx, .xls)</label>
                <input type="file" name="excel_file" class="form-control" 
                       accept=".xlsx,.xls" required id="excel-file">
            </div>
            
            <div id="excel-preview" class="d-none mb-3">
                <h6>Anteprima Dati:</h6>
                <div class="alert alert-light">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped" id="preview-table">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex justify-content-between">
                        <small class="text-muted">
                            Scontrini da creare: <span id="preview-scontrini">0</span> | 
                            Articoli da importare: <span id="preview-articoli">0</span>
                        </small>
                        <small class="text-muted">
                            Totale senza IVA: <strong>€ <span id="preview-totale">0,00</span></strong> |
                            Totale con IVA 22%: <strong>€ <span id="preview-totale-iva">0,00</span></strong>
                        </small>
                    </div>
                </div>
                
                <div id="preview-errors" class="d-none">
                    <div class="alert alert-warning">
                        <h6>Errori di Validazione:</h6>
                        <ul id="preview-errors-list"></ul>
                    </div>
                </div>
                
                <div id="preview-armonizzazione" class="d-none">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-magic me-2"></i>Suggerimenti Armonizzazione Nomi</h6>
                        <p class="mb-3">Sono stati rilevati nomi similari che potrebbero essere armonizzati. Seleziona quali accorpare:</p>
                        <div id="armonizzazione-groups"></div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-primary" onclick="applicaArmonizzazione()">
                                <i class="fas fa-check me-2"></i>Applica Armonizzazione Selezionata
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="ignoraArmonizzazione()">
                                <i class="fas fa-times me-2"></i>Ignora Suggerimenti
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <i class="fas fa-undo me-2"></i>Reset
                </button>
                <button type="submit" class="btn btn-success" id="btn-import" disabled>
                    <i class="fas fa-upload me-2"></i>Importa Scontrini
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-download me-2"></i>Template Excel</h5>
    </div>
    <div class="card-body">
        <p>Scarica il template Excel per facilitare la preparazione dei dati:</p>
        <a href="api/excel-template.php" class="btn btn-outline-success">
            <i class="fas fa-file-excel me-2"></i>Scarica Template Excel
        </a>
    </div>
</div>

<!-- Includi XLSX.js per leggere file Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
let excelData = null;
let validatedData = null;
let suggerimentiArmonizzazione = null;

document.getElementById('excel-file').addEventListener('change', handleExcelFile);

// Funzioni per calcolo similarità nomi
function levenshteinDistance(str1, str2) {
    const matrix = [];
    const n = str1.length;
    const m = str2.length;

    if (n === 0) return m;
    if (m === 0) return n;

    for (let i = 0; i <= n; i++) {
        matrix[i] = [i];
    }

    for (let j = 0; j <= m; j++) {
        matrix[0][j] = j;
    }

    for (let i = 1; i <= n; i++) {
        for (let j = 1; j <= m; j++) {
            const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
            matrix[i][j] = Math.min(
                matrix[i - 1][j] + 1,     // deletion
                matrix[i][j - 1] + 1,     // insertion
                matrix[i - 1][j - 1] + cost // substitution
            );
        }
    }

    return matrix[n][m];
}

function calculateSimilarity(str1, str2) {
    if (!str1 || !str2) return 0;
    
    // Normalizza le stringhe per il confronto
    const normalize = (str) => str.toLowerCase()
        .replace(/[àáâãäå]/g, 'a')
        .replace(/[èéêë]/g, 'e')
        .replace(/[ìíîï]/g, 'i')
        .replace(/[òóôõö]/g, 'o')
        .replace(/[ùúûü]/g, 'u')
        .replace(/[ñ]/g, 'n')
        .replace(/[ç]/g, 'c')
        .replace(/[^a-z0-9\s]/g, '') // Rimuovi punteggiatura
        .trim();
    
    const norm1 = normalize(str1);
    const norm2 = normalize(str2);
    
    if (norm1 === norm2) return 1; // Identici dopo normalizzazione
    
    const maxLen = Math.max(norm1.length, norm2.length);
    if (maxLen === 0) return 0;
    
    const distance = levenshteinDistance(norm1, norm2);
    return 1 - (distance / maxLen);
}

function trovaNoomiSimili(nomiScontrini, sogliaMinima = 0.75) {
    const gruppiSimili = [];
    const nomiGiaRaggruppati = new Set();
    
    nomiScontrini.forEach((nome1, index1) => {
        if (nomiGiaRaggruppati.has(nome1) || nome1 === 'SCONTRINO SENZA NOME  !! AGGIORNARE !!') {
            return;
        }
        
        const gruppo = {
            nomi: [nome1],
            occorrenze: 1,
            suggerito: nome1
        };
        
        nomiScontrini.forEach((nome2, index2) => {
            if (index1 !== index2 && !nomiGiaRaggruppati.has(nome2) && nome2 !== 'SCONTRINO SENZA NOME  !! AGGIORNARE !!') {
                const similarita = calculateSimilarity(nome1, nome2);
                if (similarita >= sogliaMinima) {
                    gruppo.nomi.push(nome2);
                    gruppo.occorrenze++;
                    nomiGiaRaggruppati.add(nome2);
                    
                    // Suggerisci il nome più breve o più comune
                    if (nome2.length < gruppo.suggerito.length || 
                        (nome2.length === gruppo.suggerito.length && nome2.toLowerCase() < gruppo.suggerito.toLowerCase())) {
                        gruppo.suggerito = nome2;
                    }
                }
            }
        });
        
        if (gruppo.nomi.length > 1) {
            nomiGiaRaggruppati.add(nome1);
            gruppiSimili.push(gruppo);
        }
    });
    
    return gruppiSimili;
}

function handleExcelFile(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array', cellDates: true}); // Abilita parsing date
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1, raw: false}); // raw: false per mantenere formattazione
            
            excelData = jsonData;
            processExcelData(jsonData);
        } catch (error) {
            alert('Errore nella lettura del file Excel: ' + error.message);
        }
    };
    reader.readAsArrayBuffer(file);
}

function processExcelData(data) {
    if (!data || data.length < 2) {
        alert('File Excel vuoto o formato non valido');
        return;
    }
    
    const headers = data[0];
    const tutteLeRighe = data.slice(1);
    const rows = tutteLeRighe.filter(row => {
        // Filtra righe vuote o con solo uno o due valori
        const valoriNonVuoti = row.filter(cell => cell !== null && cell !== '' && cell !== undefined);
        return valoriNonVuoti.length >= 3; // Almeno 3 valori per essere considerata valida
    });
    
    const righeScartate = tutteLeRighe.length - rows.length;
    if (righeScartate > 0) {
        console.log(`Scartate ${righeScartate} righe incomplete (con meno di 3 valori)`);
    }
    
    // Validazione e raggruppamento
    const errori = [];
    const scontrini = {};
    let articoliTotali = 0;
    let importoTotale = 0;
    
    rows.forEach((row, index) => {
        const riga = index + 2; // +2 perché saltiamo header e iniziamo da 1
        
        const numeroOrdine = row[0] ? String(row[0]).trim() : '';
        let nomeScontrino = row[1] ? String(row[1]).trim() : '';
        const dataScontrino = row[2]; // Non convertire subito in stringa
        const codiceArticolo = row[3] ? String(row[3]).trim() : '';
        const descrizione = row[4] ? String(row[4]).trim() : '';
        const qta = row[5];
        const prezzoUnitario = row[6];
        const prezzoTotale = row[7];
        
        // Debug: mostra il tipo e valore della data
        if (dataScontrino) {
            console.log(`Riga ${riga}: data tipo=${typeof dataScontrino}, valore=`, dataScontrino);
        }
        
        // Validazioni
        if (!numeroOrdine) {
            errori.push(`Riga ${riga}: Numero d'ordine obbligatorio`);
            return;
        }
        
        // Se nome scontrino è vuoto, applica valore di default
        if (!nomeScontrino) {
            nomeScontrino = 'SCONTRINO SENZA NOME  !! AGGIORNARE !!';
        }
        
        // Validazione data (opzionale ma se presente deve essere valida)
        let dataFinale = null;
        if (dataScontrino) {
            let dataObj = null;
            
            // Se la data è già un oggetto Date (da Excel)
            if (dataScontrino instanceof Date) {
                dataObj = dataScontrino;
                // Evita problemi di fuso orario usando getFullYear, getMonth, getDate
                const anno = dataObj.getFullYear();
                const mese = String(dataObj.getMonth() + 1).padStart(2, '0');
                const giorno = String(dataObj.getDate()).padStart(2, '0');
                dataFinale = `${anno}-${mese}-${giorno}`;
            } else {
                // Se è una stringa, prova i vari formati
                const dataString = String(dataScontrino).trim();
                
                // Prova formato DD/MM/YYYY (italiano completo)
                const dataRegexItalianoCompleto = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
                const matchItalianoCompleto = dataString.match(dataRegexItalianoCompleto);
                
                // Prova formato DD/MM/YY o MM/DD/YY (anno a 2 cifre)
                const dataRegexAnnoCorto = /^(\d{1,2})\/(\d{1,2})\/(\d{2})$/;
                const matchAnnoCorto = dataString.match(dataRegexAnnoCorto);
                
                // Prova formato M/D/YY (Excel americano)
                const dataRegexAmericano = /^(\d{1,2})\/(\d{1,2})\/(\d{2})$/;
                const matchAmericano = dataString.match(dataRegexAmericano);
                
                if (matchItalianoCompleto) {
                    // Formato DD/MM/YYYY
                    const [, giorno, mese, anno] = matchItalianoCompleto;
                    dataObj = new Date(anno, mese - 1, giorno);
                    
                    if (dataObj.getDate() == giorno && dataObj.getMonth() == (mese - 1) && dataObj.getFullYear() == anno) {
                        // Evita problemi di fuso orario
                        const annoFormat = dataObj.getFullYear();
                        const meseFormat = String(dataObj.getMonth() + 1).padStart(2, '0');
                        const giornoFormat = String(dataObj.getDate()).padStart(2, '0');
                        dataFinale = `${annoFormat}-${meseFormat}-${giornoFormat}`;
                    } else {
                        errori.push(`Riga ${riga}: Data scontrino non valida (${dataString})`);
                        return;
                    }
                } else if (matchAnnoCorto) {
                    // Formato con anno a 2 cifre - assumiamo 20XX per anni 00-99
                    let [, primo, secondo, annoCorto] = matchAnnoCorto;
                    let annoCompleto = parseInt(annoCorto) + 2000;
                    
                    // Determina se è formato DD/MM/YY o MM/DD/YY
                    // Se primo numero > 12, è probabilmente giorno (formato DD/MM/YY)
                    // Se secondo numero > 12, è probabilmente formato MM/DD/YY
                    let giorno, mese;
                    
                    if (parseInt(primo) > 12) {
                        // Formato DD/MM/YY
                        giorno = primo;
                        mese = secondo;
                    } else if (parseInt(secondo) > 12) {
                        // Formato MM/DD/YY
                        mese = primo;
                        giorno = secondo;
                    } else {
                        // Ambiguo - proviamo prima formato americano MM/DD/YY (come da Excel)
                        // poi formato italiano DD/MM/YY se il primo fallisce
                        mese = primo;
                        giorno = secondo;
                        
                        // Verifica se la data americana è valida
                        let dataTest = new Date(annoCompleto, mese - 1, giorno);
                        if (!(dataTest.getDate() == giorno && dataTest.getMonth() == (mese - 1))) {
                            // Se fallisce, prova formato italiano
                            giorno = primo;
                            mese = secondo;
                        }
                    }
                    
                    dataObj = new Date(annoCompleto, mese - 1, giorno);
                    
                    if (dataObj.getDate() == giorno && dataObj.getMonth() == (mese - 1) && dataObj.getFullYear() == annoCompleto) {
                        // Evita problemi di fuso orario
                        const annoFormat = dataObj.getFullYear();
                        const meseFormat = String(dataObj.getMonth() + 1).padStart(2, '0');
                        const giornoFormat = String(dataObj.getDate()).padStart(2, '0');
                        dataFinale = `${annoFormat}-${meseFormat}-${giornoFormat}`;
                        console.log(`Riga ${riga}: convertita data ${dataString} in ${dataFinale} (interpretata come ${giorno}/${mese}/${annoCompleto})`);
                    } else {
                        errori.push(`Riga ${riga}: Data scontrino non valida (${dataString})`);
                        return;
                    }
                } else {
                    // Prova formato YYYY-MM-DD
                    const dataRegexISO = /^\d{4}-\d{2}-\d{2}$/;
                    if (dataRegexISO.test(dataString)) {
                        dataObj = new Date(dataString);
                        if (!isNaN(dataObj.getTime())) {
                            dataFinale = dataString;
                        } else {
                            errori.push(`Riga ${riga}: Data scontrino non valida (${dataString})`);
                            return;
                        }
                    } else {
                        // Prova a parsare come numero seriale Excel
                        const numeroSeriale = parseFloat(dataString);
                        if (!isNaN(numeroSeriale) && numeroSeriale > 25569) {
                            // Converte numero seriale Excel in data
                            dataObj = new Date((numeroSeriale - 25569) * 86400 * 1000);
                            if (!isNaN(dataObj.getTime())) {
                                // Evita problemi di fuso orario
                                const anno = dataObj.getFullYear();
                                const mese = String(dataObj.getMonth() + 1).padStart(2, '0');
                                const giorno = String(dataObj.getDate()).padStart(2, '0');
                                dataFinale = `${anno}-${mese}-${giorno}`;
                            } else {
                                errori.push(`Riga ${riga}: Data scontrino non valida (${dataString})`);
                                return;
                            }
                        } else {
                            errori.push(`Riga ${riga}: Data scontrino deve essere nel formato DD/MM/YYYY o MM/DD/YY (ricevuto: ${dataString})`);
                            return;
                        }
                    }
                }
            }
        } else {
            // Se non c'è data, usa oggi
            dataFinale = new Date().toISOString().split('T')[0];
        }
        
        if (!descrizione) {
            errori.push(`Riga ${riga}: Descrizione articolo obbligatoria`);
            return;
        }
        
        if (!qta || isNaN(parseFloat(qta)) || parseFloat(qta) <= 0) {
            errori.push(`Riga ${riga}: Quantità deve essere un numero > 0`);
            return;
        }
        
        // Modificata validazione prezzo: ora accetta anche valori negativi
        if (prezzoUnitario === null || prezzoUnitario === undefined || prezzoUnitario === '' || isNaN(parseFloat(prezzoUnitario))) {
            errori.push(`Riga ${riga}: Prezzo unitario deve essere un numero valido`);
            return;
        }
        
        // Inizializza scontrino se non esiste
        if (!scontrini[numeroOrdine]) {
            scontrini[numeroOrdine] = {
                numero_ordine: numeroOrdine,
                nome: nomeScontrino,
                data: dataFinale,
                articoli: [],
                totale: 0
            };
        }
        
        // Verifica che il nome sia coerente (se non è quello di default)
        if (scontrini[numeroOrdine].nome !== nomeScontrino && scontrini[numeroOrdine].nome !== 'SCONTRINO SENZA NOME  !! AGGIORNARE !!') {
            errori.push(`Riga ${riga}: Nome scontrino diverso per lo stesso numero d'ordine`);
            return;
        }
        
        // Se questo scontrino ha un nome specifico, aggiornalo
        if (nomeScontrino !== 'SCONTRINO SENZA NOME  !! AGGIORNARE !!') {
            scontrini[numeroOrdine].nome = nomeScontrino;
        }
        
        // Verifica che la data sia coerente
        if (scontrini[numeroOrdine].data !== dataFinale) {
            errori.push(`Riga ${riga}: Data scontrino diversa per lo stesso numero d'ordine`);
            return;
        }
        
        const prezzoTotaleCalcolato = parseFloat(qta) * parseFloat(prezzoUnitario);
        
        // Aggiungi articolo
        scontrini[numeroOrdine].articoli.push({
            codice_articolo: codiceArticolo || null,
            descrizione_materiale: descrizione,
            qta: parseFloat(qta),
            prezzo_unitario: parseFloat(prezzoUnitario),
            prezzo_totale: prezzoTotaleCalcolato
        });
        
        scontrini[numeroOrdine].totale += prezzoTotaleCalcolato;
        articoliTotali++;
        importoTotale += prezzoTotaleCalcolato;
    });
    
    validatedData = scontrini;
    
    // Analizza nomi simili
    const nomiScontrini = Object.values(scontrini)
        .map(s => s.nome)
        .filter(nome => nome !== 'SCONTRINO SENZA NOME  !! AGGIORNARE !!');
    
    suggerimentiArmonizzazione = trovaNoomiSimili(nomiScontrini);
    
    // Mostra anteprima
    showPreview(scontrini, errori, articoliTotali, importoTotale);
}

function showPreview(scontrini, errori, articoliTotali, importoTotale) {
    const preview = document.getElementById('excel-preview');
    const table = document.getElementById('preview-table');
    const btnImport = document.getElementById('btn-import');
    
    // Headers
    table.querySelector('thead').innerHTML = `
        <tr class="table-dark">
            <th>Numero Ordine</th>
            <th>Nome Scontrino</th>
            <th>Data</th>
            <th>Articoli</th>
            <th class="text-end">Totale (senza IVA)</th>
            <th class="text-end">Totale (con IVA 22%)</th>
        </tr>
    `;
    
    // Righe scontrini
    const scontriniArray = Object.values(scontrini);
    table.querySelector('tbody').innerHTML = scontriniArray.slice(0, 10).map(scontrino => {
        const totaleConIva = scontrino.totale * 1.22;
        return `
        <tr>
            <td><strong>${escapeHtml(scontrino.numero_ordine)}</strong></td>
            <td>${escapeHtml(scontrino.nome)}</td>
            <td>${scontrino.data}</td>
            <td>
                <small>
                    ${scontrino.articoli.slice(0, 3).map(art => 
                        escapeHtml(art.descrizione_materiale)
                    ).join(', ')}
                    ${scontrino.articoli.length > 3 ? `<br><em>... e altri ${scontrino.articoli.length - 3} articoli</em>` : ''}
                </small>
            </td>
            <td class="text-end">€ ${scontrino.totale.toFixed(2)}</td>
            <td class="text-end"><strong>€ ${totaleConIva.toFixed(2)}</strong></td>
        </tr>
        `;
    }).join('');
    
    if (scontriniArray.length > 10) {
        table.querySelector('tbody').innerHTML += `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    ... e altri ${scontriniArray.length - 10} scontrini
                </td>
            </tr>
        `;
    }
    
    // Aggiorna contatori
    document.getElementById('preview-scontrini').textContent = scontriniArray.length;
    document.getElementById('preview-articoli').textContent = articoliTotali;
    document.getElementById('preview-totale').textContent = importoTotale.toFixed(2);
    document.getElementById('preview-totale-iva').textContent = (importoTotale * 1.22).toFixed(2);
    
    // Mostra errori
    const errorsDiv = document.getElementById('preview-errors');
    const errorsList = document.getElementById('preview-errors-list');
    
    if (errori.length > 0) {
        errorsList.innerHTML = errori.slice(0, 10).map(err => `<li>${escapeHtml(err)}</li>`).join('');
        if (errori.length > 10) {
            errorsList.innerHTML += `<li><em>... e altri ${errori.length - 10} errori</em></li>`;
        }
        errorsDiv.classList.remove('d-none');
        btnImport.disabled = true;
    } else {
        errorsDiv.classList.add('d-none');
        btnImport.disabled = scontriniArray.length === 0;
    }
    
    // Mostra suggerimenti armonizzazione se esistono
    showArmonizzazioneSuggerimenti();
    
    preview.classList.remove('d-none');
}

function resetForm() {
    document.getElementById('form-import').reset();
    document.getElementById('excel-preview').classList.add('d-none');
    document.getElementById('btn-import').disabled = true;
    excelData = null;
    validatedData = null;
    suggerimentiArmonizzazione = null;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showArmonizzazioneSuggerimenti() {
    const armonizzazioneDiv = document.getElementById('preview-armonizzazione');
    const groupsDiv = document.getElementById('armonizzazione-groups');
    
    if (!suggerimentiArmonizzazione || suggerimentiArmonizzazione.length === 0) {
        armonizzazioneDiv.classList.add('d-none');
        return;
    }
    
    let groupsHtml = '';
    suggerimentiArmonizzazione.forEach((gruppo, index) => {
        groupsHtml += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="gruppo-${index}" checked>
                        <label class="form-check-label fw-bold" for="gruppo-${index}">
                            Accorpa questi nomi simili:
                        </label>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-2">Nomi trovati:</small>
                            ${gruppo.nomi.map(nome => `
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="nome-gruppo-${index}" 
                                           id="nome-${index}-${nome.replace(/[^a-zA-Z0-9]/g, '')}" 
                                           value="${escapeHtml(nome)}" 
                                           ${nome === gruppo.suggerito ? 'checked' : ''}>
                                    <label class="form-check-label" for="nome-${index}-${nome.replace(/[^a-zA-Z0-9]/g, '')}">
                                        <span class="badge ${nome === gruppo.suggerito ? 'bg-primary' : 'bg-secondary'} me-1">
                                            ${escapeHtml(nome)}
                                        </span>
                                    </label>
                                </div>
                            `).join('')}
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-2">O scrivi un nome personalizzato:</small>
                            <input type="text" class="form-control form-control-sm" 
                                   id="custom-nome-${index}" 
                                   placeholder="Nome personalizzato...">
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    groupsDiv.innerHTML = groupsHtml;
    armonizzazioneDiv.classList.remove('d-none');
}

function applicaArmonizzazione() {
    if (!suggerimentiArmonizzazione || !validatedData) return;
    
    let applicatiCambiamenti = false;
    
    suggerimentiArmonizzazione.forEach((gruppo, index) => {
        const checkbox = document.getElementById(`gruppo-${index}`);
        if (!checkbox.checked) return;
        
        // Determina il nome da usare
        let nomeFinale = null;
        
        // Controlla se c'è un nome personalizzato
        const customInput = document.getElementById(`custom-nome-${index}`);
        if (customInput.value.trim()) {
            nomeFinale = customInput.value.trim();
        } else {
            // Prendi il nome selezionato dai radio button
            const selectedRadio = document.querySelector(`input[name="nome-gruppo-${index}"]:checked`);
            if (selectedRadio) {
                nomeFinale = selectedRadio.value;
            }
        }
        
        if (!nomeFinale) return;
        
        // Applica il cambiamento a tutti gli scontrini del gruppo
        Object.values(validatedData).forEach(scontrino => {
            if (gruppo.nomi.includes(scontrino.nome)) {
                scontrino.nome = nomeFinale;
                applicatiCambiamenti = true;
            }
        });
    });
    
    if (applicatiCambiamenti) {
        // Nascondi i suggerimenti
        document.getElementById('preview-armonizzazione').classList.add('d-none');
        
        // Ricarica l'anteprima con i nomi aggiornati
        const scontriniArray = Object.values(validatedData);
        const articoliTotali = scontriniArray.reduce((sum, s) => sum + s.articoli.length, 0);
        const importoTotale = scontriniArray.reduce((sum, s) => sum + s.totale, 0);
        
        showPreview(validatedData, [], articoliTotali, importoTotale);
        
        // Mostra messaggio di successo
        alert(`Armonizzazione applicata con successo! I nomi degli scontrini sono stati aggiornati.`);
    }
}

function ignoraArmonizzazione() {
    document.getElementById('preview-armonizzazione').classList.add('d-none');
    suggerimentiArmonizzazione = null;
}

// Gestione submit form
document.getElementById('form-import').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validatedData) {
        alert('Nessun dato da importare');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('excel_data', JSON.stringify(validatedData));
    
    const btnImport = document.getElementById('btn-import');
    const originalText = btnImport.innerHTML;
    btnImport.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importazione...';
    btnImport.disabled = true;
    
    fetch('api/import-excel-massivo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ricarica la pagina per mostrare i risultati
            window.location.href = 'import-excel.php?success=1&risultati=' + encodeURIComponent(JSON.stringify(data.risultati));
        } else {
            alert('Errore nell\'importazione: ' + data.error);
            btnImport.innerHTML = originalText;
            btnImport.disabled = false;
        }
    })
    .catch(error => {
        alert('Errore di comunicazione: ' + error.message);
        btnImport.innerHTML = originalText;
        btnImport.disabled = false;
    });
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>