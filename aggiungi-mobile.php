<?php
require_once 'includes/bootstrap.php';
require_once 'config.php';
define('APP_NAME', 'NomeApp');
Auth::requireLogin();

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();
$error = '';
$success = '';

// Per responsabili e admin, recupera la lista utenti disponibili
$available_users = Auth::getAvailableUsersForReceipts();

if ($_POST) {
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $data_scontrino = $_POST['data_scontrino'] ?? '';
    $lordo = Utils::safeFloat($_POST['lordo'] ?? '');
    $da_versare = Utils::safeFloat($_POST['da_versare'] ?? '');
    $note = Utils::sanitizeString($_POST['note'] ?? '');
    $selected_user_id = isset($_POST['utente_id']) ? (int)$_POST['utente_id'] : null;
    
    // Se da_versare è vuoto o zero, usa l'importo lordo
    if ($da_versare <= 0) {
        $da_versare = $lordo;
    }
    
    // Determina l'utente e la filiale per lo scontrino
    $target_user_id = $current_user['id'];
    $target_filiale_id = $current_user['filiale_id'];
    
    // Se è responsabile o admin e ha selezionato un utente specifico
    if ((Auth::isResponsabile() || Auth::isAdmin()) && $selected_user_id) {
        // Verifica che l'utente selezionato sia autorizzato
        $selected_user = null;
        foreach ($available_users as $user) {
            if ($user['id'] == $selected_user_id) {
                $selected_user = $user;
                break;
            }
        }
        
        if ($selected_user) {
            // Ottieni i dettagli completi dell'utente selezionato
            $user_details = $db->fetchOne("
                SELECT id, filiale_id FROM utenti WHERE id = ? AND attivo = 1
            ", [$selected_user_id]);
            
            if ($user_details) {
                $target_user_id = $user_details['id'];
                $target_filiale_id = $user_details['filiale_id'];
            }
        }
    }
    
    // Validazione
    if (empty($nome)) {
        $error = 'Il nome dello scontrino è obbligatorio';
    } elseif (empty($data_scontrino)) {
        $error = 'La data dello scontrino è obbligatoria';
    } elseif ($lordo <= 0) {
        $error = 'L\'importo lordo deve essere maggiore di zero';
    } elseif ($da_versare < 0) {
        $error = 'L\'importo da versare non può essere negativo';
    } elseif ($da_versare > $lordo) {
        $error = 'L\'importo da versare non può essere maggiore dell\'importo lordo';
    } else {
        try {
            // Inserisci lo scontrino associandolo all'utente e filiale determinati
            $db->query("
                INSERT INTO scontrini (nome, data_scontrino, lordo, da_versare, note, utente_id, filiale_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$nome, $data_scontrino, $lordo, $da_versare, $note, $target_user_id, $target_filiale_id]);
            
            $success_message = 'Scontrino aggiunto con successo!';
            if ($target_user_id !== $current_user['id']) {
                // Trova il nome dell'utente per il messaggio
                $target_user_name = '';
                foreach ($available_users as $user) {
                    if ($user['id'] == $target_user_id) {
                        $target_user_name = $user['nome'];
                        break;
                    }
                }
                $success_message = "Scontrino aggiunto con successo per l'utente: " . $target_user_name;
            }
            
            Utils::setFlashMessage('success', $success_message);
            Utils::redirect('index.php');
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Aggiungi Scontrino - <?php echo APP_NAME; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.4;
        }
        
        .mobile-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .mobile-header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
        }
        
        .container {
            padding: 15px;
            max-width: 100%;
        }
        
        .mobile-form {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .form-group label .required {
            color: #dc3545;
            font-size: 18px;
            margin-left: 3px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control:invalid {
            border-color: #dc3545;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-primary:hover, .btn-primary:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover, .btn-secondary:active {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .button-group .btn {
            flex: 1;
            min-width: 140px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .user-selector {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .user-selector label {
            color: #1976d2;
            font-weight: 700;
        }
        
        .help-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }
        
        .amount-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 480px) {
            .amount-inputs {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .container {
                padding: 10px;
            }
            
            .mobile-form {
                padding: 15px;
            }
        }
        
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 8px;
        }
        
        .quick-amount {
            padding: 8px;
            background: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .quick-amount:hover, .quick-amount:active {
            background: #007bff;
            color: white;
            transform: scale(1.05);
        }
        
        .floating-save {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50px;
            padding: 15px 25px;
            font-size: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .floating-save {
                bottom: 10px;
                right: 10px;
                left: 10px;
                border-radius: 8px;
                position: fixed;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-header">
        <h1>📝 Aggiungi Scontrino</h1>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
        <div class="alert alert-danger">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="mobile-form">
            <?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
            <div class="user-selector">
                <label for="utente_id">👤 Utente</label>
                <select id="utente_id" name="utente_id" class="form-control">
                    <option value="">Me stesso</option>
                    <?php foreach ($available_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo (isset($selected_user_id) && $selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome">🏷️ Nome <span class="required">*</span></label>
                <input type="text" 
                       id="nome" 
                       name="nome" 
                       class="form-control"
                       placeholder="Es: Cena cliente, Materiali..." 
                       value="<?php echo htmlspecialchars($nome ?? ''); ?>" 
                       required 
                       autocomplete="off">
                <div class="help-text">Descrivi brevemente lo scontrino</div>
            </div>
            
            <div class="form-group">
                <label for="data_scontrino">📅 Data <span class="required">*</span></label>
                <input type="date" 
                       id="data_scontrino" 
                       name="data_scontrino" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($data_scontrino ?? date('Y-m-d')); ?>" 
                       required>
            </div>
            
            <div class="amount-inputs">
                <div class="form-group">
                    <label for="lordo">💰 Importo <span class="required">*</span></label>
                    <input type="number" 
                           id="lordo" 
                           name="lordo" 
                           class="form-control"
                           placeholder="0.00" 
                           step="0.01" 
                           min="0.01"
                           value="<?php echo htmlspecialchars($lordo ?? ''); ?>" 
                           required>
                    <div class="quick-amounts">
                        <div class="quick-amount" onclick="setAmount('lordo', '10')">10€</div>
                        <div class="quick-amount" onclick="setAmount('lordo', '20')">20€</div>
                        <div class="quick-amount" onclick="setAmount('lordo', '50')">50€</div>
                        <div class="quick-amount" onclick="setAmount('lordo', '100')">100€</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="da_versare">💸 Da Versare</label>
                    <input type="number" 
                           id="da_versare" 
                           name="da_versare" 
                           class="form-control"
                           placeholder="Uguale all'importo" 
                           step="0.01" 
                           min="0"
                           value="<?php echo htmlspecialchars($da_versare ?? ''); ?>">
                    <div class="help-text">Lascia vuoto se uguale all'importo</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="note">📝 Note</label>
                <textarea id="note" 
                          name="note" 
                          class="form-control"
                          placeholder="Note aggiuntive (opzionale)..."
                          rows="3"><?php echo htmlspecialchars($note ?? ''); ?></textarea>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    💾 Salva
                </button>
                <a href="index.php" class="btn btn-secondary">
                    ❌ Annulla
                </a>
            </div>
        </form>
    </div>

    <script>
        // Auto-copia dell'importo lordo in "da versare"
        document.getElementById('lordo').addEventListener('input', function() {
            const daVersare = document.getElementById('da_versare');
            if (!daVersare.value) {
                daVersare.value = this.value;
            }
        });
        
        // Funzione per impostare importi rapidi
        function setAmount(fieldId, amount) {
            document.getElementById(fieldId).value = amount;
            // Se stiamo impostando il lordo e da_versare è vuoto, copialo
            if (fieldId === 'lordo') {
                const daVersare = document.getElementById('da_versare');
                if (!daVersare.value) {
                    daVersare.value = amount;
                }
            }
        }
        
        // Vibrazione tattile su dispositivi mobili (se supportata)
        function vibrate() {
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        }
        
        // Aggiungi vibrazione ai pulsanti
        document.querySelectorAll('.btn, .quick-amount').forEach(element => {
            element.addEventListener('click', vibrate);
        });
        
        // Auto-focus sul primo campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nome').focus();
        });
        
        // Suggerimenti nome scontrino (simulazione autocomplete)
        const nomiComuni = [
            'Cena cliente',
            'Pranzo lavoro', 
            'Materiali ufficio',
            'Benzina',
            'Parcheggio',
            'Taxi',
            'Hotel',
            'Formazione',
            'Software',
            'Telefono'
        ];
        
        document.getElementById('nome').addEventListener('input', function() {
            // Implementazione base di suggerimenti
            // In una versione completa potresti fare una chiamata AJAX per ottenere nomi già usati
        });
    </script>
</body>
</html>