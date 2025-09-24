<?php
require_once 'includes/bootstrap.php';
Auth::requireLogin();

$db = Database::getInstance();

// Statistiche scontrini
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
    WHERE archiviato = 0
");

// Calcoli finanziari
$totale_incassare = $stats['totale_incassare'] ?? 0;
$totale_incassato = $stats['totale_incassato'] ?? 0;
$totale_da_incassare = $stats['totale_da_incassare'] ?? 0;
$totale_versato = $stats['totale_versato'] ?? 0;
$totale_da_versare = $stats['totale_da_versare'] ?? 0;
$ancora_da_versare = $totale_da_versare; // Usa il calcolo corretto dal database
$cassa = $totale_incassato - $totale_versato;

// Ultimi 5 scontrini inseriti
$ultimi_scontrini = $db->fetchAll("
    SELECT id, nome, data_scontrino, lordo, da_versare, incassato, versato, archiviato
    FROM scontrini 
    ORDER BY created_at DESC 
    LIMIT 5
");

$page_title = 'Dashboard - ' . SITE_NAME;
$page_header = 'Dashboard - Gestione Scontrini Fiscali';

ob_start();
?>

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
    <h3>Ultimi 5 Scontrini Inseriti (tutti)</h3>
    <?php if ($ultimi_scontrini): ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Data</th>
                <th>Lordo</th>
                <th>Da Versare</th>
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
    <a href="aggiungi.php" class="btn btn-success btn-lg">
        <i class="fas fa-plus"></i> Aggiungi Nuovo Scontrino
    </a>
    <a href="lista.php" class="btn btn-primary btn-lg">
        <i class="fas fa-list"></i> Visualizza Tutti gli Scontrini
    </a>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>