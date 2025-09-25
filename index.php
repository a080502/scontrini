<?php
require_once 'includes/bootstrap.php';
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
        COUNT(CASE WHEN incassato = 1 AND archiviato = 0 THEN 1 END) as num_incassati,
        COUNT(CASE WHEN incassato = 0 AND archiviato = 0 THEN 1 END) as num_da_incassare,
        COUNT(CASE WHEN archiviato = 1 THEN 1 END) as num_archiviati,
        SUM(CASE WHEN archiviato = 0 THEN lordo ELSE 0 END) as totale_incassare,
        SUM(CASE WHEN incassato = 1 AND archiviato = 0 THEN lordo ELSE 0 END) as totale_incassato,
        SUM(CASE WHEN incassato = 0 AND archiviato = 0 THEN lordo ELSE 0 END) as totale_da_incassare,
        SUM(CASE WHEN versato = 1 AND archiviato = 0 THEN COALESCE(da_versare, lordo) ELSE 0 END) as totale_versato,
        SUM(CASE WHEN versato = 0 AND incassato = 1 AND archiviato = 0 THEN COALESCE(da_versare, lordo) ELSE 0 END) as totale_da_versare
    FROM scontrini 
    WHERE archiviato = 0" . $where_clause, $query_params);

// Calcoli finanziari
$totale_incassare = $stats['totale_incassare'] ?? 0;
$totale_incassato = $stats['totale_incassato'] ?? 0;
$totale_da_incassare = $stats['totale_da_incassare'] ?? 0;
$totale_versato = $stats['totale_versato'] ?? 0;
$totale_da_versare = $stats['totale_da_versare'] ?? 0;
$ancora_da_versare = $totale_da_versare; // Usa il calcolo corretto dal database
$cassa = $totale_incassato - $totale_versato;

// Ultimi 5 scontrini inseriti con filtro per ruolo
$ultimi_scontrini = $db->fetchAll("
    SELECT s.id, s.nome, s.data_scontrino, s.lordo, s.da_versare, s.incassato, s.versato, s.archiviato,
           u.nome as utente_nome, f.nome as filiale_nome
    FROM scontrini s
    LEFT JOIN utenti u ON s.utente_id = u.id
    LEFT JOIN filiali f ON s.filiale_id = f.id
    WHERE 1=1" . $where_clause_with_prefix . "
    ORDER BY s.created_at DESC 
    LIMIT 5
", $query_params_with_prefix);

$page_title = 'Dashboard - ' . SITE_NAME;
$page_header = 'Dashboard - Gestione Scontrini Fiscali';

ob_start();
?>

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
    <?php if ($ultimi_scontrini): ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Data</th>
                <th>Lordo</th>
                <th>Da Versare</th>
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
            <tr class="<?php echo $scontrino['incassato'] ? 'incassato' : ''; ?>">
                <td><?php echo htmlspecialchars($scontrino['nome']); ?></td>
                <td><?php echo Utils::formatDate($scontrino['data_scontrino']); ?></td>
                <td class="euro"><?php echo Utils::formatCurrency($scontrino['lordo']); ?></td>
                <td class="euro"><?php echo Utils::formatCurrency($scontrino['da_versare'] ?? $scontrino['lordo']); ?></td>
                <?php if (Auth::isAdmin() || Auth::isResponsabile()): ?>
                    <td><?php echo htmlspecialchars($scontrino['utente_nome'] ?? 'N/A'); ?></td>
                    <?php if (Auth::isAdmin()): ?>
                        <td><?php echo htmlspecialchars($scontrino['filiale_nome'] ?? 'N/A'); ?></td>
                    <?php endif; ?>
                <?php endif; ?>
                <td>
                    <?php if ($scontrino['archiviato']): ?>
                        <span class="badge" style="background-color: #6c757d;">Archiviato</span>
                    <?php elseif ($scontrino['versato']): ?>
                        <span class="badge badge-success">Versato</span>
                    <?php elseif ($scontrino['incassato']): ?>
                        <span class="badge badge-success">Incassato</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Da Incassare</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="modifica.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php if (!$scontrino['archiviato']): ?>
                        <?php if (!$scontrino['incassato']): ?>
                        <a href="incassa.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-money-bill"></i>
                        </a>
                        <?php elseif (!$scontrino['versato']): ?>
                        <a href="versa.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-university"></i>
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
    <?php echo Utils::smartLink('aggiungi.php', null, '<i class="fas fa-plus"></i> Aggiungi Nuovo Scontrino', 'btn btn-success btn-lg'); ?>
    <a href="lista.php" class="btn btn-primary btn-lg">
        <i class="fas fa-list"></i> Visualizza Tutti gli Scontrini
    </a>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>