<?php
require_once 'includes/bootstrap.php';
Auth::requireLogin();

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();

// Filtri per archivio
$anno = $_GET['anno'] ?? '';
$mese = $_GET['mese'] ?? '';

// Costruisci query con filtri e permessi utente
$where_conditions = ["archiviato = 1"];
$params = [];

// Filtri per permessi utente
if (Auth::isAdmin()) {
    // Admin vede tutto - nessun filtro aggiuntivo
} elseif (Auth::isResponsabile()) {
    // Responsabile vede solo la sua filiale
    $where_conditions[] = "filiale_id = ?";
    $params[] = $current_user['filiale_id'];
} else {
    // Utente normale vede solo i suoi scontrini
    $where_conditions[] = "utente_id = ?";
    $params[] = $current_user['id'];
}

if ($anno) {
    $where_conditions[] = "YEAR(data_scontrino) = ?";
    $params[] = $anno;
}

if ($mese) {
    $where_conditions[] = "MONTH(data_scontrino) = ?";
    $params[] = $mese;
}

$where_clause = implode(" AND ", $where_conditions);

// Recupera scontrini archiviati
$scontrini = $db->fetchAll("
    SELECT * FROM scontrini 
    WHERE $where_clause 
    ORDER BY data_archiviazione DESC, data_scontrino DESC
", $params);

// Statistiche archivio
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as totale,
        SUM(lordo) as totale_importo,
        SUM(CASE WHEN incassato = 1 THEN lordo ELSE 0 END) as totale_incassato,
        SUM(CASE WHEN versato = 1 THEN lordo ELSE 0 END) as totale_versato
    FROM scontrini 
    WHERE $where_clause
", $params);

// Anni disponibili per filtro (solo dall'archivio e rispettando i permessi)
$anni_where = $where_conditions; // Riusa le stesse condizioni di filtro
$anni = $db->fetchAll("
    SELECT DISTINCT YEAR(data_scontrino) as anno 
    FROM scontrini 
    WHERE " . implode(" AND ", $anni_where) . "
    ORDER BY anno DESC
", $params);

$page_title = 'Archivio Scontrini - ' . SITE_NAME;
$page_header = 'Archivio Scontrini';

ob_start();
?>

<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <form method="GET" style="display: inline-block;">
        
        <label for="anno">Anno:</label>
        <select name="anno" id="anno" onchange="this.form.submit()">
            <option value="">Tutti</option>
            <?php if ($anni): ?>
                <?php foreach ($anni as $a): ?>
                <option value="<?php echo $a['anno']; ?>" <?php echo $anno == $a['anno'] ? 'selected' : ''; ?>>
                    <?php echo $a['anno']; ?>
                </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <label for="mese">Mese:</label>
        <select name="mese" id="mese" onchange="this.form.submit()">
            <option value="">Tutti</option>
            <?php for ($i = 1; $i <= 12; $i++): ?>
            <option value="<?php echo $i; ?>" <?php echo $mese == $i ? 'selected' : ''; ?>>
                <?php 
                $months = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                          'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
                echo $months[$i-1];
                ?>
            </option>
            <?php endfor; ?>
        </select>
        
        <button type="submit" class="btn btn-sm">Applica</button>
        <a href="archivio.php" class="btn btn-sm btn-secondary">Reset</a>
    </form>
</div>

<?php if ($stats['totale'] > 0): ?>
<div class="totali">
    <h4>Riepilogo Archivio</h4>
    <p><strong>Scontrini Archiviati:</strong> <?php echo $stats['totale']; ?></p>
    <p><strong>Totale Importo:</strong> <span class="euro"><?php echo Utils::formatCurrency($stats['totale_importo']); ?></span></p>
    <p><strong>Totale Incassato:</strong> <span class="euro"><?php echo Utils::formatCurrency($stats['totale_incassato']); ?></span></p>
    <p><strong>Totale Versato:</strong> <span class="euro"><?php echo Utils::formatCurrency($stats['totale_versato']); ?></span></p>
</div>
<?php endif; ?>

<?php if ($scontrini): ?>
<table>
    <thead>
        <tr>
            <th>Nome</th>
            <th>Data Scontrino</th>
            <th>Lordo</th>
            <th>Da Versare</th>
            <th>Stato</th>
            <th>Data Archiviazione</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($scontrini as $scontrino): ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($scontrino['nome']); ?>
                <?php if ($scontrino['note']): ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($scontrino['note']); ?></small>
                <?php endif; ?>
            </td>
            <td><?php echo Utils::formatDate($scontrino['data_scontrino']); ?></td>
            <td class="euro"><?php echo Utils::formatCurrency($scontrino['lordo']); ?></td>
            <td class="euro"><?php echo Utils::formatCurrency($scontrino['da_versare'] ?? $scontrino['lordo']); ?></td>
            <td>
                <span class="badge" style="background-color: #6c757d;">Archiviato</span>
                <?php if ($scontrino['versato']): ?>
                    <span class="badge badge-success">Versato</span>
                <?php elseif ($scontrino['incassato']): ?>
                    <span class="badge badge-success">Incassato</span>
                <?php else: ?>
                    <span class="badge badge-warning">Non Incassato</span>
                <?php endif; ?>
            </td>
            <td><?php echo Utils::formatDateTime($scontrino['data_archiviazione']); ?></td>
            <td>
                <a href="riattiva.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success" title="Riattiva">
                    <i class="fas fa-undo"></i> Riattiva
                </a>
                
                <a href="elimina.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-danger" 
                   onclick="return confermaEliminazione('Sei sicuro di voler eliminare definitivamente questo scontrino archiviato?')" title="Elimina">
                    <i class="fas fa-trash"></i>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Nessuno scontrino archiviato trovato con i filtri selezionati.
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 30px;">
    <a href="lista.php" class="btn btn-primary">
        <i class="fas fa-list"></i> Torna alla Lista Attivi
    </a>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>