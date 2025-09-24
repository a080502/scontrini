<?php
require_once 'includes/bootstrap.php';
Auth::requireAdminOrResponsabile(); // Admin e responsabili possono gestire utenti

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
                    throw new Exception("Solo gli amministratori possono creare utenti");
                }
                
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $nome = trim($_POST['nome']);
                $ruolo = $_POST['ruolo'];
                $filiale_id = $_POST['filiale_id'] ? (int)$_POST['filiale_id'] : null;
                
                if (empty($username) || empty($password) || empty($nome)) {
                    throw new Exception("Tutti i campi obbligatori devono essere compilati");
                }
                
                if (strlen($password) < 6) {
                    throw new Exception("La password deve essere di almeno 6 caratteri");
                }
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $db->query("
                    INSERT INTO utenti (username, password, nome, ruolo, filiale_id) 
                    VALUES (?, ?, ?, ?, ?)
                ", [$username, $password_hash, $nome, $ruolo, $filiale_id]);
                
                $success_message = "Utente creato con successo";
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nome = trim($_POST['nome']);
                $ruolo = $_POST['ruolo'];
                $filiale_id = $_POST['filiale_id'] ? (int)$_POST['filiale_id'] : null;
                
                // Verifica permessi
                $utente_da_modificare = $db->fetchOne("SELECT * FROM utenti WHERE id = ?", [$id]);
                if (!$utente_da_modificare) {
                    throw new Exception("Utente non trovato");
                }
                
                if (!Auth::isAdmin()) {
                    // I responsabili possono modificare solo utenti della loro filiale
                    if ($utente_da_modificare['filiale_id'] != $current_user['filiale_id']) {
                        throw new Exception("Non hai i permessi per modificare questo utente");
                    }
                    // I responsabili non possono modificare il ruolo
                    $ruolo = $utente_da_modificare['ruolo'];
                    $filiale_id = $utente_da_modificare['filiale_id'];
                }
                
                if (empty($nome)) {
                    throw new Exception("Il nome è obbligatorio");
                }
                
                $db->query("
                    UPDATE utenti 
                    SET nome = ?, ruolo = ?, filiale_id = ?
                    WHERE id = ?
                ", [$nome, $ruolo, $filiale_id, $id]);
                
                if (!empty($_POST['new_password'])) {
                    if (strlen($_POST['new_password']) < 6) {
                        throw new Exception("La password deve essere di almeno 6 caratteri");
                    }
                    $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $db->query("UPDATE utenti SET password = ? WHERE id = ?", [$new_password_hash, $id]);
                }
                
                $success_message = "Utente aggiornato con successo";
                break;
                
            case 'delete':
                if (!Auth::isAdmin()) {
                    throw new Exception("Solo gli amministratori possono eliminare utenti");
                }
                
                $id = (int)$_POST['id'];
                
                if ($id == $current_user['id']) {
                    throw new Exception("Non puoi eliminare il tuo stesso account");
                }
                
                // Verifica se l'utente ha scontrini associati
                $scontrini_count = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE utente_id = ?", [$id]);
                if ($scontrini_count['count'] > 0) {
                    throw new Exception("Non è possibile eliminare l'utente perché ha scontrini associati");
                }
                
                $db->query("DELETE FROM utenti WHERE id = ?", [$id]);
                $success_message = "Utente eliminato con successo";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Recupera utenti visibili
if (Auth::isAdmin()) {
    $utenti = $db->fetchAll("
        SELECT u.*, f.nome as filiale_nome,
               (SELECT COUNT(*) FROM scontrini s WHERE s.utente_id = u.id) as scontrini_count
        FROM utenti u
        LEFT JOIN filiali f ON u.filiale_id = f.id
        ORDER BY u.created_at DESC
    ");
} else {
    // Responsabili vedono solo utenti della loro filiale
    $utenti = $db->fetchAll("
        SELECT u.*, f.nome as filiale_nome,
               (SELECT COUNT(*) FROM scontrini s WHERE s.utente_id = u.id) as scontrini_count
        FROM utenti u
        LEFT JOIN filiali f ON u.filiale_id = f.id
        WHERE u.filiale_id = ?
        ORDER BY u.created_at DESC
    ", [$current_user['filiale_id']]);
}

// Recupera filiali per i dropdown (solo admin)
$filiali = [];
if (Auth::isAdmin()) {
    $filiali = $db->fetchAll("SELECT id, nome FROM filiali WHERE attiva = 1 ORDER BY nome");
}

$page_title = 'Gestione Utenti';
?>

<?php include 'includes/layout.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-users"></i> Gestione Utenti
                        <?php if (!Auth::isAdmin()): ?>
                            <small class="text-muted">- <?= htmlspecialchars($current_user['filiale_nome']) ?></small>
                        <?php endif; ?>
                    </h3>
                    <?php if (Auth::isAdmin()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-user-plus"></i> Nuovo Utente
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

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4><?= count($utenti) ?></h4>
                                    <small>Totale Utenti</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($utenti, fn($u) => $u['ruolo'] === 'admin')) ?></h4>
                                    <small>Amministratori</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($utenti, fn($u) => $u['ruolo'] === 'responsabile')) ?></h4>
                                    <small>Responsabili</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($utenti, fn($u) => $u['ruolo'] === 'utente')) ?></h4>
                                    <small>Utenti</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Utente</th>
                                    <th>Ruolo</th>
                                    <th>Filiale</th>
                                    <th>Scontrini</th>
                                    <th>Registrato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utenti as $utente): ?>
                                    <tr class="<?= $utente['id'] == $current_user['id'] ? 'table-info' : '' ?>">
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($utente['nome']) ?></strong>
                                                <?php if ($utente['id'] == $current_user['id']): ?>
                                                    <span class="badge bg-info ms-1">Tu</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($utente['username']) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $ruolo_config = [
                                                'admin' => ['Amministratore', 'bg-danger', 'fa-crown'],
                                                'responsabile' => ['Responsabile', 'bg-info', 'fa-user-tie'],
                                                'utente' => ['Utente', 'bg-success', 'fa-user']
                                            ];
                                            $config = $ruolo_config[$utente['ruolo']] ?? ['Sconosciuto', 'bg-secondary', 'fa-question'];
                                            ?>
                                            <span class="badge <?= $config[1] ?>">
                                                <i class="fas <?= $config[2] ?>"></i> <?= $config[0] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($utente['filiale_nome']): ?>
                                                <span class="badge bg-outline-primary">
                                                    <?= htmlspecialchars($utente['filiale_nome']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Non assegnata</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= $utente['scontrini_count'] ?></span>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($utente['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if (Auth::isAdmin() || ($utente['filiale_id'] == $current_user['filiale_id'] && $utente['ruolo'] == 'utente')): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editUtente(<?= htmlspecialchars(json_encode($utente)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (Auth::isAdmin() && $utente['id'] != $current_user['id']): ?>
                                                <form method="post" class="d-inline" 
                                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo utente? Questa operazione non può essere annullata!')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $utente['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
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

<!-- Modal per aggiungere utente -->
<?php if (Auth::isAdmin()): ?>
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nuovo Utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="add_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="add_password" name="password" required minlength="6">
                        <small class="text-muted">Minimo 6 caratteri</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_nome" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="add_nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_ruolo" class="form-label">Ruolo *</label>
                        <select class="form-control" id="add_ruolo" name="ruolo" required>
                            <option value="utente">Utente</option>
                            <option value="responsabile">Responsabile</option>
                            <option value="admin">Amministratore</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_filiale_id" class="form-label">Filiale</label>
                        <select class="form-control" id="add_filiale_id" name="filiale_id">
                            <option value="">Seleziona filiale</option>
                            <?php foreach ($filiali as $filiale): ?>
                                <option value="<?= $filiale['id'] ?>"><?= htmlspecialchars($filiale['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Utente</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal per modificare utente -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" readonly>
                        <small class="text-muted">Non modificabile</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required>
                    </div>
                    
                    <?php if (Auth::isAdmin()): ?>
                        <div class="mb-3">
                            <label for="edit_ruolo" class="form-label">Ruolo *</label>
                            <select class="form-control" id="edit_ruolo" name="ruolo" required>
                                <option value="utente">Utente</option>
                                <option value="responsabile">Responsabile</option>
                                <option value="admin">Amministratore</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_filiale_id" class="form-label">Filiale</label>
                            <select class="form-control" id="edit_filiale_id" name="filiale_id">
                                <option value="">Seleziona filiale</option>
                                <?php foreach ($filiali as $filiale): ?>
                                    <option value="<?= $filiale['id'] ?>"><?= htmlspecialchars($filiale['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="edit_new_password" class="form-label">Nuova Password</label>
                        <input type="password" class="form-control" id="edit_new_password" name="new_password" minlength="6">
                        <small class="text-muted">Lascia vuoto per non modificare</small>
                    </div>
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
function editUtente(utente) {
    document.getElementById('edit_id').value = utente.id;
    document.getElementById('edit_username').value = utente.username;
    document.getElementById('edit_nome').value = utente.nome;
    
    <?php if (Auth::isAdmin()): ?>
    document.getElementById('edit_ruolo').value = utente.ruolo;
    document.getElementById('edit_filiale_id').value = utente.filiale_id || '';
    <?php endif; ?>
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>