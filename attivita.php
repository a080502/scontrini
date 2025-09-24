<?php
require_once 'includes/bootstrap.php';
Auth::requireLogin();

$db = Database::getInstance();

// Recupera tutte le attività recenti
$attivita = $db->fetchAll("
    SELECT 
        s.*,
        DATE(s.created_at) as data_creazione,
        s.data_incasso,
        s.data_versamento,
        s.data_archiviazione
    FROM scontrini s
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
       OR s.data_incasso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
       OR s.data_versamento >= DATE_SUB(NOW(), INTERVAL 30 DAY)
       OR s.data_archiviazione >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY 
        GREATEST(
            COALESCE(s.created_at, '1970-01-01'),
            COALESCE(s.data_incasso, '1970-01-01'),
            COALESCE(s.data_versamento, '1970-01-01'),
            COALESCE(s.data_archiviazione, '1970-01-01')
        ) DESC
    LIMIT 50
");

// Crea un array di eventi per il timeline
$eventi = [];
foreach ($attivita as $scontrino) {
    // Creazione
    $eventi[] = [
        'data' => $scontrino['created_at'],
        'tipo' => 'creazione',
        'scontrino' => $scontrino,
        'descrizione' => "Scontrino '{$scontrino['nome']}' creato"
    ];
    
    // Incasso
    if ($scontrino['data_incasso']) {
        $eventi[] = [
            'data' => $scontrino['data_incasso'],
            'tipo' => 'incasso',
            'scontrino' => $scontrino,
            'descrizione' => "Scontrino '{$scontrino['nome']}' incassato"
        ];
    }
    
    // Versamento
    if ($scontrino['data_versamento']) {
        $eventi[] = [
            'data' => $scontrino['data_versamento'],
            'tipo' => 'versamento',
            'scontrino' => $scontrino,
            'descrizione' => "Scontrino '{$scontrino['nome']}' versato"
        ];
    }
    
    // Archiviazione
    if ($scontrino['data_archiviazione']) {
        $eventi[] = [
            'data' => $scontrino['data_archiviazione'],
            'tipo' => 'archiviazione',
            'scontrino' => $scontrino,
            'descrizione' => "Scontrino '{$scontrino['nome']}' archiviato"
        ];
    }
}

// Ordina gli eventi per data decrescente
usort($eventi, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

// Limita a 30 eventi più recenti
$eventi = array_slice($eventi, 0, 30);

$page_title = 'Attività Recenti - ' . SITE_NAME;
$page_header = 'Attività Recenti (Ultimi 30 giorni)';

ob_start();
?>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding: 15px 0 15px 50px;
    border-left: 2px solid #e9ecef;
}

.timeline-item:last-child {
    border-left: none;
}

.timeline-icon {
    position: absolute;
    left: -8px;
    top: 20px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #007bff;
}

.timeline-icon.creazione {
    background: #28a745;
}

.timeline-icon.incasso {
    background: #ffc107;
}

.timeline-icon.versamento {
    background: #17a2b8;
}

.timeline-icon.archiviazione {
    background: #6c757d;
}

.timeline-content {
    background: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-left: 10px;
}

.timeline-date {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 5px;
}
</style>

<?php if ($eventi): ?>
<div class="timeline">
    <?php foreach ($eventi as $evento): ?>
    <div class="timeline-item">
        <div class="timeline-icon <?php echo $evento['tipo']; ?>"></div>
        <div class="timeline-content">
            <div class="timeline-date">
                <i class="fas fa-clock"></i> <?php echo Utils::formatDateTime($evento['data']); ?>
            </div>
            <h5>
                <?php
                $icone = [
                    'creazione' => 'fas fa-plus-circle',
                    'incasso' => 'fas fa-money-bill',
                    'versamento' => 'fas fa-university',
                    'archiviazione' => 'fas fa-archive'
                ];
                ?>
                <i class="<?php echo $icone[$evento['tipo']]; ?>"></i>
                <?php echo htmlspecialchars($evento['descrizione']); ?>
            </h5>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p><strong>Data Scontrino:</strong> <?php echo Utils::formatDate($evento['scontrino']['data_scontrino']); ?></p>
                    <p><strong>Importo:</strong> <?php echo Utils::formatCurrency($evento['scontrino']['lordo']); ?></p>
                    <?php if ($evento['scontrino']['note']): ?>
                    <p><strong>Note:</strong> <?php echo htmlspecialchars($evento['scontrino']['note']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!$evento['scontrino']['archiviato']): ?>
                    <a href="modifica.php?id=<?php echo $evento['scontrino']['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Modifica
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Nessuna attività registrata negli ultimi 30 giorni.
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 30px;">
    <a href="index.php" class="btn btn-primary">
        <i class="fas fa-home"></i> Torna alla Dashboard
    </a>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>