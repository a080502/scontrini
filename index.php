<?php
require_once 'includes/installation_check.php';
requireBootstrap();
Auth::requireLogin();

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();

// Filtri per dashboard (opzionali)
$filters = [
    'filiale_id' => $_GET['filiale_id'] ?? '',
    'utente_id' => $_GET['utente_id'] ?? '',
    'nome_filter' => $_GET['nome_filter'] ?? ''
];

// Prepara filtri per le query basati sul ruolo dell'utente
$where_clause = "";
$where_clause_with_prefix = ""; // Per query con JOIN
$query_params = [];
$query_params_with_prefix = []; // Per query con JOIN

// Applica filtri avanzati usando la nuova funzione
$advanced_filter_data = Utils::buildAdvancedFilters($db, $current_user, $filters, ''); // Nessun prefisso per query semplici
$advanced_filter_data_with_prefix = Utils::buildAdvancedFilters($db, $current_user, $filters, 's.'); // Prefisso per JOIN

if (!empty($advanced_filter_data['where_conditions'])) {
    $where_clause = " AND " . implode(" AND ", $advanced_filter_data['where_conditions']);
    $query_params = $advanced_filter_data['params'];
}

if (!empty($advanced_filter_data_with_prefix['where_conditions'])) {
    $where_clause_with_prefix = " AND " . implode(" AND ", $advanced_filter_data_with_prefix['where_conditions']);
    $query_params_with_prefix = $advanced_filter_data_with_prefix['params'];
}

// Statistiche scontrini con filtro per ruolo
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as num_scontrini,
        COUNT(CASE WHEN stato IN ('incassato', 'versato') THEN 1 END) as num_incassati,
        COUNT(CASE WHEN stato = 'attivo' THEN 1 END) as num_da_incassare,
        COUNT(CASE WHEN stato = 'archiviato' THEN 1 END) as num_archiviati,
        SUM(CASE WHEN stato != 'archiviato' THEN lordo ELSE 0 END) as totale_incassare,
        SUM(CASE WHEN stato IN ('incassato', 'versato') THEN lordo ELSE 0 END) as totale_incassato,
        SUM(CASE WHEN stato = 'attivo' THEN lordo ELSE 0 END) as totale_da_incassare,
        SUM(CASE WHEN stato = 'versato' THEN COALESCE(da_versare, lordo) ELSE 0 END) as totale_versato,
        SUM(CASE WHEN stato = 'incassato' THEN COALESCE(da_versare, lordo) ELSE 0 END) as totale_da_versare,
        SUM(CASE WHEN stato IN ('attivo', 'incassato') THEN COALESCE(da_versare, lordo) ELSE 0 END) as totale_ancora_da_versare
    FROM scontrini 
    WHERE stato != 'archiviato'" . $where_clause, $query_params);

// Calcoli finanziari
$totale_incassare = $stats['totale_incassare'] ?? 0;
$totale_incassato = $stats['totale_incassato'] ?? 0;
$totale_da_incassare = $stats['totale_da_incassare'] ?? 0;
$totale_versato = $stats['totale_versato'] ?? 0;
$totale_da_versare = $stats['totale_da_versare'] ?? 0;
$ancora_da_versare = $stats['totale_ancora_da_versare'] ?? 0; // Include attivo + incassato
$cassa = $totale_incassato - $totale_versato;
$differenza_incassare_versare = $totale_da_incassare - $ancora_da_versare;

// Ultimi 5 scontrini inseriti con filtro per ruolo
$ultimi_scontrini = $db->fetchAll("
    SELECT s.id, s.numero, s.nome_persona, s.data, s.lordo, s.da_versare, s.stato,
           u.nome as utente_nome, f.nome as filiale_nome,
           COALESCE(s.nome_persona, s.numero) as nome_display
    FROM scontrini s
    LEFT JOIN utenti u ON s.utente_id = u.id
    LEFT JOIN filiali f ON s.filiale_id = f.id
    WHERE s.stato != 'archiviato'" . $where_clause_with_prefix . "
    ORDER BY s.created_at DESC 
    LIMIT 5
", $query_params_with_prefix);

$page_title = 'Dashboard - ' . SITE_NAME;
$page_header = 'Dashboard - Gestione Scontrini Fiscali';

ob_start();
?>

<style>
.column-toggle-btn {
    margin: 10px 0;
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.column-toggle-btn:hover {
    background-color: #0056b3;
}
.hidden-column {
    display: none !important;
}
</style>

<?php if (Auth::isAdmin() || Auth::isResponsabile()): ?>
    <?php echo Utils::renderAdvancedFiltersForm($db, $current_user, $filters, 'index.php'); ?>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['num_scontrini'] ?? 0; ?></div>
        <div>Scontrini Attivi</div>
    </div>
    <div class="stat-card success">
        <div class="stat-number"><?php echo $stats['num_incassati'] ?? 0; ?></div>
        <div>Incassati (Attivi)</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-number"><?php echo $stats['num_da_incassare'] ?? 0; ?></div>
        <div>Da Incassare</div>
    </div>
    <div class="stat-card info">
        <div class="stat-number"><?php echo $stats['num_archiviati'] ?? 0; ?></div>
        <div>Archiviati</div>
    </div>
</div>

<div class="totali">
    <h3>Riepilogo Finanziario Scontrini Attivi</h3>
    <p><strong>Totale da Incassare (lordo):</strong> <span class="euro"><?php echo Utils::formatCurrency($totale_incassare); ?></span></p>
    <p><strong>Già Incassato:</strong> <span class="euro"><?php echo Utils::formatCurrency($totale_incassato); ?></span></p>
    <p><strong>Ancora da Incassare:</strong> <span class="euro"><?php echo Utils::formatCurrency($totale_da_incassare); ?></span></p>
    <hr>
    <p><strong>Già Versato:</strong> <span class="euro"><?php echo Utils::formatCurrency($totale_versato); ?></span></p>
    <p><strong>Ancora da Versare:</strong> <span class="euro"><?php echo Utils::formatCurrency($ancora_da_versare); ?></span></p>
    <hr>
    <p><strong>Differenza (Da Incassare - Da Versare):</strong> <span class="euro"><?php echo Utils::formatCurrency($differenza_incassare_versare); ?></span></p>
    <hr>
    <p><strong>Cassa Attuale (Incassato - Versato):</strong> <span class="euro"><?php echo Utils::formatCurrency($cassa); ?></span></p>
</div>

<div class="card">
    <h3>Ultimi 5 Scontrini 
        <?php if (Auth::isAdmin()): ?>
            (di tutto il sistema)
        <?php elseif (Auth::isResponsabile()): ?>
            (della tua filiale)
        <?php else: ?>
            (tuoi)
        <?php endif; ?>
    </h3>
    
    <button id="toggleDaVersareBtn" class="column-toggle-btn" onclick="toggleDaVersareColumn()">
        <i class="fas fa-eye-slash"></i> Nascondi colonna "Da Versare"
    </button>
    
    <?php if ($ultimi_scontrini): ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Data</th>
                <th>Da Incassare</th>
                <th class="da-versare-column">Da Versare</th>
                <?php if (Auth::isAdmin() || Auth::isResponsabile()): ?>
                    <th>Utente</th>
                    <?php if (Auth::isAdmin()): ?>
                        <th>Filiale</th>
                    <?php endif; ?>
                <?php endif; ?>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ultimi_scontrini as $scontrino): ?>
            <tr class="<?php echo in_array($scontrino['stato'], ['incassato', 'versato']) ? 'incassato' : ''; ?>">
                <td><?php echo htmlspecialchars($scontrino['nome_display']); ?></td>
                <td><?php echo Utils::formatDate($scontrino['data']); ?></td>
                <td class="euro"><?php echo Utils::formatCurrency($scontrino['lordo']); ?></td>
                <td class="euro da-versare-column"><?php echo Utils::formatCurrency($scontrino['da_versare'] ?? $scontrino['lordo']); ?></td>
                <?php if (Auth::isAdmin() || Auth::isResponsabile()): ?>
                    <td><?php echo htmlspecialchars($scontrino['utente_nome'] ?? 'N/A'); ?></td>
                    <?php if (Auth::isAdmin()): ?>
                        <td><?php echo htmlspecialchars($scontrino['filiale_nome'] ?? 'N/A'); ?></td>
                    <?php endif; ?>
                <?php endif; ?>
                <td>
                    <?php if ($scontrino['stato'] === 'archiviato'): ?>
                        <span class="badge" style="background-color: #6c757d;">Archiviato</span>
                    <?php elseif ($scontrino['stato'] === 'versato'): ?>
                        <span class="badge badge-success">Versato</span>
                    <?php elseif ($scontrino['stato'] === 'incassato'): ?>
                        <span class="badge badge-success">Incassato</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Da Incassare</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="modifica.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-warning" title="Modifica scontrino">
                        <i class="fas fa-edit"></i> Modifica
                    </a>
                    <?php if ($scontrino['stato'] !== 'archiviato'): ?>
                        <?php if ($scontrino['stato'] === 'attivo'): ?>
                        <a href="incassa.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success" title="Incassa scontrino">
                            <i class="fas fa-money-bill"></i> Incassa
                        </a>
                        <?php elseif ($scontrino['stato'] === 'incassato'): ?>
                        <a href="versa.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success" title="Versa importo">
                            <i class="fas fa-university"></i> Versa
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>Nessuno scontrino presente.</p>
    <?php endif; ?>
</div>

<div style="text-align: center; margin-top: 30px;">
    <?php echo Utils::smartLink('aggiungi.php', null, '<i class="fas fa-plus"></i> Aggiungi Nuovo Scontrino', 'btn btn-success btn-lg', true); ?>
    <a href="lista.php" class="btn btn-primary btn-lg">
        <i class="fas fa-list"></i> Visualizza Tutti gli Scontrini
    </a>
</div>

<script>
let daVersareVisible = true;

function toggleDaVersareColumn() {
    const columns = document.querySelectorAll('.da-versare-column');
    const button = document.getElementById('toggleDaVersareBtn');
    
    if (daVersareVisible) {
        columns.forEach(col => col.classList.add('hidden-column'));
        button.innerHTML = '<i class="fas fa-eye"></i> Mostra colonna "Da Versare"';
        daVersareVisible = false;
    } else {
        columns.forEach(col => col.classList.remove('hidden-column'));
        button.innerHTML = '<i class="fas fa-eye-slash"></i> Nascondi colonna "Da Versare"';
        daVersareVisible = true;
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>