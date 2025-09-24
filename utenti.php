<?php
require_once 'includes/bootstrap.php';
Auth::requireAdmin(); // Solo admin possono gestire utenti

$db = Database::getInstance();
$error = '';
$success = '';

// Gestione eliminazione utente
if (isset($_GET['elimina'])) {
    $user_id = (int)$_GET['elimina'];
    
    // Non permettere di eliminare se stesso
    if ($user_id == $_SESSION['user_id']) {
        Utils::setFlashMessage('error', 'Non puoi eliminare il tuo stesso account!');
    } else {
        try {
            $user = $db->fetchOne("SELECT username FROM utenti WHERE id = ?", [$user_id]);
            if ($user) {
                $db->query("DELETE FROM utenti WHERE id = ?", [$user_id]);
                Utils::setFlashMessage('success', "Utente '{$user['username']}' eliminato con successo!");
            } else {
                Utils::setFlashMessage('error', 'Utente non trovato!');
            }
        } catch (Exception $e) {
            Utils::setFlashMessage('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    Utils::redirect('utenti.php');
}

// Recupera tutti gli utenti
$utenti = $db->fetchAll("
    SELECT id, username, nome, ruolo, created_at
    FROM utenti 
    ORDER BY created_at DESC
");

$page_title = 'Gestione Utenti - ' . SITE_NAME;
$page_header = 'Gestione Utenti';

ob_start();
?>

<div style="margin-bottom: 20px;">
    <a href="aggiungi_utente.php" class="btn btn-success">
        <i class="fas fa-user-plus"></i> Aggiungi Nuovo Utente
    </a>
</div>

<?php if ($utenti): ?>
<div class="card">
    <h3>Utenti del Sistema (<?php echo count($utenti); ?>)</h3>
    
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Nome Completo</th>
                <th>Ruolo</th>
                <th>Data Creazione</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($utenti as $utente): ?>
            <tr <?php echo $utente['id'] == $_SESSION['user_id'] ? 'style="background-color: #e3f2fd;"' : ''; ?>>
                <td>
                    <strong><?php echo htmlspecialchars($utente['username']); ?></strong>
                    <?php if ($utente['id'] == $_SESSION['user_id']): ?>
                        <span class="badge" style="background-color: #2196f3; color: white; font-size: 10px;">TU</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($utente['nome']); ?></td>
                <td>
                    <?php if ($utente['ruolo'] === 'admin'): ?>
                        <span class="badge badge-success">
                            <i class="fas fa-crown"></i> Amministratore
                        </span>
                    <?php else: ?>
                        <span class="badge badge-warning">
                            <i class="fas fa-user"></i> Utente
                        </span>
                    <?php endif; ?>
                </td>
                <td><?php echo Utils::formatDateTime($utente['created_at']); ?></td>
                <td>
                    <a href="modifica_utente.php?id=<?php echo $utente['id']; ?>" class="btn btn-sm btn-warning" title="Modifica">
                        <i class="fas fa-edit"></i>
                    </a>
                    
                    <?php if ($utente['id'] != $_SESSION['user_id']): ?>
                    <a href="utenti.php?elimina=<?php echo $utente['id']; ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confermaEliminazione('Sei sicuro di voler eliminare l\'utente <?php echo htmlspecialchars($utente['username']); ?>? Questa operazione non può essere annullata!')" 
                       title="Elimina">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php else: ?>
                    <span class="btn btn-sm btn-secondary" style="opacity: 0.5;" title="Non puoi eliminare te stesso">
                        <i class="fas fa-ban"></i>
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h4>Statistiche Utenti</h4>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($utenti); ?></div>
            <div>Totale Utenti</div>
        </div>
        <div class="stat-card success">
            <div class="stat-number"><?php echo count(array_filter($utenti, fn($u) => $u['ruolo'] === 'admin')); ?></div>
            <div>Amministratori</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?php echo count(array_filter($utenti, fn($u) => $u['ruolo'] === 'user')); ?></div>
            <div>Utenti Standard</div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Nessun utente presente nel sistema.
</div>
<?php endif; ?>

<div class="card">
    <h4>Informazioni Sicurezza</h4>
    <p><strong>Il tuo account:</strong> <?php echo htmlspecialchars($_SESSION['nome']); ?> (<?php echo $_SESSION['ruolo']; ?>)</p>
    <p><strong>Ultimo accesso:</strong> Sessione corrente</p>
    <p><strong>Timeout sessione:</strong> <?php echo SESSION_LIFETIME / 60; ?> minuti di inattività</p>
    
    <div style="margin-top: 15px;">
        <a href="modifica_utente.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-primary">
            <i class="fas fa-user-edit"></i> Modifica il Tuo Profilo
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>