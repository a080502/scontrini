<?php
require_once 'includes/bootstrap.php';
require_once 'includes/image_manager.php';
Auth::requireLogin();

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();

// Filtri
$filtro = $_GET['filtro'] ?? 'tutti';
$anno = $_GET['anno'] ?? '';
$mese = $_GET['mese'] ?? '';

// Nuovi filtri avanzati
$filters = [
    'filiale_id' => $_GET['filiale_id'] ?? '',
    'utente_id' => $_GET['utente_id'] ?? '',
    'nome_filter' => $_GET['nome_filter'] ?? ''
];

// Costruisci query con filtri e permessi
$where_conditions = ["s.archiviato = 0"];
$params = [];

// Applica filtri avanzati usando la nuova funzione
$advanced_filter_data = Utils::buildAdvancedFilters($db, $current_user, $filters);
$where_conditions = array_merge($where_conditions, $advanced_filter_data['where_conditions']);
$params = array_merge($params, $advanced_filter_data['params']);

if ($anno) {
    $where_conditions[] = "YEAR(s.data_scontrino) = ?";
    $params[] = $anno;
}

if ($mese) {
    $where_conditions[] = "MONTH(s.data_scontrino) = ?";
    $params[] = $mese;
}

switch ($filtro) {
    case 'da_incassare':
        $where_conditions[] = "s.incassato = 0";
        break;
    case 'incassati':
        $where_conditions[] = "s.incassato = 1";
        break;
    case 'da_versare':
        $where_conditions[] = "s.incassato = 1 AND s.versato = 0";
        break;
    case 'versati':
        $where_conditions[] = "s.versato = 1";
        break;
}

// Funzione helper per creare URL che mantengono i filtri avanzati
function createFilterUrl($base_filtro, $anno, $mese, $filters) {
    $params = [
        'filtro' => $base_filtro,
        'anno' => $anno,
        'mese' => $mese
    ];
    
    if (!empty($filters['filiale_id'])) {
        $params['filiale_id'] = $filters['filiale_id'];
    }
    if (!empty($filters['utente_id'])) {
        $params['utente_id'] = $filters['utente_id'];
    }
    if (!empty($filters['nome_filter'])) {
        $params['nome_filter'] = $filters['nome_filter'];
    }
    
    return '?' . http_build_query($params);
}

$where_clause = implode(" AND ", $where_conditions);

// Recupera scontrini con informazioni utente e filiale
$scontrini = $db->fetchAll("
    SELECT s.*, 
           u.nome as utente_nome, u.username as utente_username,
           f.nome as filiale_nome
    FROM scontrini s 
    LEFT JOIN utenti u ON s.utente_id = u.id
    LEFT JOIN filiali f ON s.filiale_id = f.id
    WHERE $where_clause 
    ORDER BY s.nome ASC, s.data_scontrino DESC, s.created_at DESC
", $params);

// Raggruppa scontrini per nome
$scontrini_raggruppati = [];
foreach ($scontrini as $scontrino) {
    $nome = $scontrino['nome'];
    if (!isset($scontrini_raggruppati[$nome])) {
        $scontrini_raggruppati[$nome] = [];
    }
    $scontrini_raggruppati[$nome][] = $scontrino;
}

// Statistiche per i filtri correnti
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as totale,
        SUM(s.lordo) as totale_importo,
        SUM(CASE WHEN s.incassato = 1 THEN s.lordo ELSE 0 END) as totale_incassato,
        SUM(CASE WHEN s.versato = 1 THEN s.lordo ELSE 0 END) as totale_versato
    FROM scontrini s
    LEFT JOIN utenti u ON s.utente_id = u.id
    LEFT JOIN filiali f ON s.filiale_id = f.id
    WHERE $where_clause
", $params);

// Anni disponibili per filtro (con stesso controllo permessi)
$anni_where = $where_conditions;
// Rimuovi i filtri di anno/mese per ottenere tutti gli anni disponibili
$anni_where = array_filter($anni_where, function($condition) {
    return !str_contains($condition, 'YEAR(') && !str_contains($condition, 'MONTH(');
});
$anni_clause = implode(" AND ", $anni_where);
$anni_params = array_slice($params, 0, count($anni_where) - count($where_conditions) + count($anni_where));

$anni = $db->fetchAll("
    SELECT DISTINCT YEAR(s.data_scontrino) as anno 
    FROM scontrini s
    LEFT JOIN utenti u ON s.utente_id = u.id
    LEFT JOIN filiali f ON s.filiale_id = f.id
    " . ($anni_clause ? "WHERE $anni_clause" : "") . "
    ORDER BY anno DESC
", $anni_params);

$page_title = 'Lista Scontrini - ' . SITE_NAME;
$page_header = 'Lista Scontrini Attivi';

ob_start();
?>

<div class="filtri">
    <h4>Filtri</h4>
    <a href="<?php echo createFilterUrl('tutti', $anno, $mese, $filters); ?>" 
       class="btn <?php echo $filtro === 'tutti' ? 'active' : ''; ?>">Tutti</a>
    <a href="<?php echo createFilterUrl('da_incassare', $anno, $mese, $filters); ?>" 
       class="btn <?php echo $filtro === 'da_incassare' ? 'active' : ''; ?>">Da Incassare</a>
    <a href="<?php echo createFilterUrl('incassati', $anno, $mese, $filters); ?>" 
       class="btn <?php echo $filtro === 'incassati' ? 'active' : ''; ?>">Incassati</a>
    <a href="<?php echo createFilterUrl('da_versare', $anno, $mese, $filters); ?>" 
       class="btn <?php echo $filtro === 'da_versare' ? 'active' : ''; ?>">Da Versare</a>
    <a href="<?php echo createFilterUrl('versati', $anno, $mese, $filters); ?>" 
       class="btn <?php echo $filtro === 'versati' ? 'active' : ''; ?>">Versati</a>
</div>

<?php echo Utils::renderAdvancedFiltersForm($db, $current_user, $filters, 'lista.php'); ?>

<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <form method="GET" style="display: inline-block;">
        <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
        <?php if ($filters['filiale_id']): ?>
            <input type="hidden" name="filiale_id" value="<?php echo htmlspecialchars($filters['filiale_id']); ?>">
        <?php endif; ?>
        <?php if ($filters['utente_id']): ?>
            <input type="hidden" name="utente_id" value="<?php echo htmlspecialchars($filters['utente_id']); ?>">
        <?php endif; ?>
        <?php if ($filters['nome_filter']): ?>
            <input type="hidden" name="nome_filter" value="<?php echo htmlspecialchars($filters['nome_filter']); ?>">
        <?php endif; ?>
        
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
        <a href="lista.php" class="btn btn-sm btn-secondary">Reset</a>
    </form>
</div>

<?php if ($stats['totale'] > 0): ?>
<div class="totali">
    <h4>Riepilogo Selezione</h4>
    <p><strong>Scontrini:</strong> <?php echo $stats['totale']; ?></p>
    <p><strong>Totale Importo:</strong> <span class="euro"><?php echo Utils::formatCurrency($stats['totale_importo']); ?></span></p>
    <p><strong>Totale Incassato:</strong> <span class="euro"><?php echo Utils::formatCurrency($stats['totale_incassato']); ?></span></p>
    <p><strong>Totale Versato:</strong> <span class="euro"><?php echo Utils::formatCurrency($stats['totale_versato']); ?></span></p>
</div>
<?php endif; ?>

<?php if ($scontrini_raggruppati): ?>
<div class="scontrini-raggruppati">
    <?php foreach ($scontrini_raggruppati as $nome => $scontrini_gruppo): ?>
        <?php 
        // Calcola totali per il gruppo
        $totale_gruppo_lordo = 0;
        $totale_gruppo_da_versare = 0;
        $count_incassati = 0;
        $count_versati = 0;
        
        foreach ($scontrini_gruppo as $scontrino) {
            $totale_gruppo_lordo += $scontrino['lordo'];
            $totale_gruppo_da_versare += $scontrino['da_versare'] ?? $scontrino['lordo'];
            if ($scontrino['incassato']) $count_incassati++;
            if ($scontrino['versato']) $count_versati++;
        }
        ?>
        
        <div class="gruppo-nome">
            <h3 class="nome-gruppo">
                <?php echo htmlspecialchars($nome); ?>
                <small>(<?php echo count($scontrini_gruppo); ?> scontrini)</small>
            </h3>
            
            <table class="tabella-gruppo">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Foto</th>
                        <th>Lordo</th>
                        <th>Da Versare</th>
                        <th>Stato</th>
                        <th>Date Operazioni</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scontrini_gruppo as $scontrino): ?>
                    <tr class="<?php echo $scontrino['incassato'] ? 'incassato' : ''; ?>">
                        <td><?php echo Utils::formatDate($scontrino['data_scontrino']); ?>
                            <?php if ($scontrino['note']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($scontrino['note']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if (!empty($scontrino['foto_scontrino']) && file_exists($scontrino['foto_scontrino'])): ?>
                                <a href="<?php echo ImageManager::getPhotoUrl($scontrino['foto_scontrino']); ?>" 
                                   target="_blank" title="Visualizza foto scontrino">
                                    <img src="<?php echo ImageManager::getPhotoUrl($scontrino['foto_scontrino']) . '&thumbnail=1'; ?>" 
                                         style="max-width: 50px; max-height: 50px; border-radius: 4px; border: 1px solid #ddd;"
                                         alt="Foto scontrino">
                                </a>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 12px;">Nessuna foto</span>
                            <?php endif; ?>
                        </td>
                        <td class="euro"><?php echo Utils::formatCurrency($scontrino['lordo']); ?></td>
                        <td class="euro"><?php echo Utils::formatCurrency($scontrino['da_versare'] ?? $scontrino['lordo']); ?></td>
                        <td>
                            <?php if ($scontrino['versato']): ?>
                                <span class="badge badge-success">Versato</span>
                            <?php elseif ($scontrino['incassato']): ?>
                                <span class="badge badge-success">Incassato</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Da Incassare</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($scontrino['data_incasso']): ?>
                            <small>Incassato: <?php echo Utils::formatDateTime($scontrino['data_incasso']); ?></small><br>
                            <?php endif; ?>
                            <?php if ($scontrino['data_versamento']): ?>
                            <small>Versato: <?php echo Utils::formatDateTime($scontrino['data_versamento']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="modifica.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-warning" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if (!$scontrino['incassato']): ?>
                            <a href="incassa.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success" title="Incassa">
                                <i class="fas fa-money-bill"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($scontrino['incassato'] && !$scontrino['versato']): ?>
                            <a href="versa.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-success" title="Versa">
                                <i class="fas fa-university"></i>
                            </a>
                            <a href="annulla_incasso.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-warning" title="Annulla Incasso">
                                <i class="fas fa-undo"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($scontrino['versato']): ?>
                            <a href="annulla_versamento.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-warning" title="Annulla Versamento">
                                <i class="fas fa-undo"></i>
                            </a>
                            <?php endif; ?>
                            
                            <a href="archivia.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-secondary" title="Archivia">
                                <i class="fas fa-archive"></i>
                            </a>
                            
                            <a href="elimina.php?id=<?php echo $scontrino['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confermaEliminazione('Sei sicuro di voler eliminare questo scontrino?')" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Totali del gruppo -->
            <div class="totali-gruppo">
                <div class="totali-riga">
                    <strong>Totali per <?php echo htmlspecialchars($nome); ?>:</strong>
                    <span class="totale-importo">Lordo: <span class="euro"><?php echo Utils::formatCurrency($totale_gruppo_lordo); ?></span></span>
                    <span class="totale-da-versare">Da Versare: <span class="euro"><?php echo Utils::formatCurrency($totale_gruppo_da_versare); ?></span></span>
                    <span class="stato-gruppo">
                        <?php echo $count_incassati; ?>/<?php echo count($scontrini_gruppo); ?> incassati - 
                        <?php echo $count_versati; ?>/<?php echo count($scontrini_gruppo); ?> versati
                    </span>
                </div>
            </div>
        </div>
        <hr class="gruppo-separator">
    <?php endforeach; ?>
</div>
<?php elseif ($scontrini): ?>
<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Nessuno scontrino trovato con i filtri selezionati.
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 30px;">
    <?php echo Utils::smartLink('aggiungi.php', null, '<i class="fas fa-plus"></i> Aggiungi Nuovo Scontrino', 'btn btn-success', true); ?>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>