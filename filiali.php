<?php
require_once 'includes/bootstrap.php';

Auth::requireAdminOrResponsabile();

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();
$success_message = '';
$error_message = '';

// Gestione delle azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                if (!Auth::isAdmin()) {
                    throw new Exception("Non hai i permessi per creare filiali");
                }
                
                $nome = trim($_POST['nome']);
                $indirizzo = trim($_POST['indirizzo']);
                $telefono = trim($_POST['telefono']);
                
                if (empty($nome)) {
                    throw new Exception("Il nome della filiale è obbligatorio");
                }
                
                $db->query("
                    INSERT INTO filiali (nome, indirizzo, telefono, attiva) 
                    VALUES (?, ?, ?, 1)
                ", [$nome, $indirizzo, $telefono]);
                
                $success_message = "Filiale creata con successo";
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nome = trim($_POST['nome']);
                $indirizzo = trim($_POST['indirizzo']);
                $telefono = trim($_POST['telefono']);
                $responsabile_id = $_POST['responsabile_id'] ? (int)$_POST['responsabile_id'] : null;
                
                // Verifica permessi
                if (!Auth::isAdmin() && $id != $current_user['filiale_id']) {
                    throw new Exception("Non hai i permessi per modificare questa filiale");
                }
                
                if (empty($nome)) {
                    throw new Exception("Il nome della filiale è obbligatorio");
                }
                
                $db->query("
                    UPDATE filiali 
                    SET nome = ?, indirizzo = ?, telefono = ?, responsabile_id = ?
                    WHERE id = ?
                ", [$nome, $indirizzo, $telefono, $responsabile_id, $id]);
                
                $success_message = "Filiale aggiornata con successo";
                break;
                
            case 'toggle':
                if (!Auth::isAdmin()) {
                    throw new Exception("Non hai i permessi per disattivare filiali");
                }
                
                $id = (int)$_POST['id'];
                $db->query("UPDATE filiali SET attiva = !attiva WHERE id = ?", [$id]);
                $success_message = "Stato filiale aggiornato";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Recupera filiali visibili
if (Auth::isAdmin()) {
    $filiali = $db->query("
        SELECT f.*, u.nome as responsabile_nome, u.username as responsabile_username
        FROM filiali f
        LEFT JOIN utenti u ON f.responsabile_id = u.id
        ORDER BY f.nome
    ");
} else {
    $filiali = $db->query("
        SELECT f.*, u.nome as responsabile_nome, u.username as responsabile_username
        FROM filiali f
        LEFT JOIN utenti u ON f.responsabile_id = u.id
        WHERE f.id = ?
        ORDER BY f.nome
    ", [$current_user['filiale_id']]);
}

// Recupera utenti responsabili per il dropdown (solo admin)
$responsabili = [];
if (Auth::isAdmin()) {
    $responsabili = $db->query("
        SELECT id, nome, username 
        FROM utenti 
        WHERE ruolo IN ('admin', 'responsabile') 
        ORDER BY nome
    ");
}

$page_title = 'Gestione Filiali';
?>

<?php include 'includes/layout.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-building"></i> Gestione Filiali
                    </h3>
                    <?php if (Auth::isAdmin()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus"></i> Nuova Filiale
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Indirizzo</th>
                                    <th>Telefono</th>
                                    <th>Responsabile</th>
                                    <th>Stato</th>
                                    <th>Utenti</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filiali as $filiale): ?>
                                    <?php 
                                    $utenti_filiale = $db->fetchOne("SELECT COUNT(*) as count FROM utenti WHERE filiale_id = ?", [$filiale['id']]);
                                    ?>
                                    <tr class="<?= $filiale['attiva'] ? '' : 'table-secondary' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($filiale['nome']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($filiale['indirizzo']) ?></td>
                                        <td><?= htmlspecialchars($filiale['telefono']) ?></td>
                                        <td>
                                            <?php if ($filiale['responsabile_nome']): ?>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($filiale['responsabile_nome']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Non assegnato</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($filiale['attiva']): ?>
                                                <span class="badge bg-success">Attiva</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Disattivata</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= $utenti_filiale['count'] ?> utenti
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (Auth::isAdmin() || $filiale['id'] == $current_user['filiale_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editFiliale(<?= htmlspecialchars(json_encode($filiale)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (Auth::isAdmin()): ?>
                                                <form method="post" class="d-inline" 
                                                      onsubmit="return confirm('Sei sicuro di voler <?= $filiale['attiva'] ? 'disattivare' : 'riattivare' ?> questa filiale?')">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="id" value="<?= $filiale['id'] ?>">
                                                    <button type="submit" class="btn btn-sm <?= $filiale['attiva'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                                        <i class="fas <?= $filiale['attiva'] ? 'fa-pause' : 'fa-play' ?>"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal per aggiungere filiale -->
<?php if (Auth::isAdmin()): ?>
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nuova Filiale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="add_nome" class="form-label">Nome Filiale *</label>
                        <input type="text" class="form-control" id="add_nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_indirizzo" class="form-label">Indirizzo</label>
                        <textarea class="form-control" id="add_indirizzo" name="indirizzo" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_telefono" class="form-label">Telefono</label>
                        <input type="text" class="form-control" id="add_telefono" name="telefono">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Filiale</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal per modificare filiale -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Filiale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">Nome Filiale *</label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_indirizzo" class="form-label">Indirizzo</label>
                        <textarea class="form-control" id="edit_indirizzo" name="indirizzo" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_telefono" class="form-label">Telefono</label>
                        <input type="text" class="form-control" id="edit_telefono" name="telefono">
                    </div>
                    
                    <?php if (Auth::isAdmin()): ?>
                        <div class="mb-3">
                            <label for="edit_responsabile_id" class="form-label">Responsabile</label>
                            <select class="form-control" id="edit_responsabile_id" name="responsabile_id">
                                <option value="">Seleziona responsabile</option>
                                <?php foreach ($responsabili as $resp): ?>
                                    <option value="<?= $resp['id'] ?>"><?= htmlspecialchars($resp['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editFiliale(filiale) {
    document.getElementById('edit_id').value = filiale.id;
    document.getElementById('edit_nome').value = filiale.nome;
    document.getElementById('edit_indirizzo').value = filiale.indirizzo || '';
    document.getElementById('edit_telefono').value = filiale.telefono || '';
    
    <?php if (Auth::isAdmin()): ?>
    const responsabileSelect = document.getElementById('edit_responsabile_id');
    if (responsabileSelect) {
        responsabileSelect.value = filiale.responsabile_id || '';
    }
    <?php endif; ?>
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>