<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';
require_once '../includes/scontrino_dettagli.php';

// Verifica autenticazione
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Autenticazione richiesta']);
    exit;
}

// Verifica autorizzazioni - Solo admin e responsabili possono importare Excel
if (!Auth::isAdmin() && !Auth::isResponsabile()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non hai i permessi per questa operazione']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

try {
    $db = Database::getInstance();
    $current_user = Auth::getCurrentUser();
    $dettagli_manager = new ScontrinoDettagli();
    
    // Recupera dati dal form
    $excel_data = json_decode($_POST['excel_data'] ?? '', true);
    $selected_user_id = isset($_POST['utente_id']) && !empty($_POST['utente_id']) ? (int)$_POST['utente_id'] : null;
    
    if (!$excel_data) {
        throw new Exception('Dati Excel non validi o mancanti');
    }
    
    // Determina l'utente target
    $target_user_id = $current_user['id'];
    $target_filiale_id = $current_user['filiale_id'];
    
    if ($selected_user_id && (Auth::isResponsabile() || Auth::isAdmin())) {
        // Verifica che l'utente selezionato sia autorizzato
        $available_users = Auth::getAvailableUsersForReceipts();
        $selected_user = null;
        
        foreach ($available_users as $user) {
            if ($user['id'] == $selected_user_id) {
                $selected_user = $user;
                break;
            }
        }
        
        if ($selected_user) {
            $user_details = $db->fetchOne("
                SELECT id, filiale_id FROM utenti WHERE id = ? AND attivo = 1
            ", [$selected_user_id]);
            
            if ($user_details) {
                $target_user_id = $user_details['id'];
                $target_filiale_id = $user_details['filiale_id'];
            }
        }
    }
    
    // Costante IVA
    $iva_percentage = 0.22; // 22%
    
    // Inizia transazione
    $db->beginTransaction();
    
    $risultati = [
        'scontrini_creati' => 0,
        'dettagli_creati' => 0,
        'errori' => [],
        'totale_importo' => 0,
        'scontrini_ids' => []
    ];
    
    foreach ($excel_data as $scontrino_data) {
        try {
            // Valida dati scontrino
            if (empty($scontrino_data['numero_ordine'])) {
                throw new Exception("Numero d'ordine mancante");
            }
            
            if (empty($scontrino_data['nome'])) {
                $scontrino_data['nome'] = 'SCONTRINO SENZA NOME  !! AGGIORNARE !!';
            }
            
            if (empty($scontrino_data['data'])) {
                $scontrino_data['data'] = date('Y-m-d');
            }
            
            // Validazione data
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scontrino_data['data'])) {
                // Prova a convertire dal formato DD/MM/YYYY a YYYY-MM-DD
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $scontrino_data['data'], $matches)) {
                    $giorno = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $mese = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                    $anno = $matches[3];
                    
                    // Verifica che sia una data valida
                    if (checkdate($mese, $giorno, $anno)) {
                        $scontrino_data['data'] = "$anno-$mese-$giorno";
                    } else {
                        throw new Exception("Data non valida per ordine: " . $scontrino_data['numero_ordine']);
                    }
                } else {
                    throw new Exception("Formato data non valido per ordine: " . $scontrino_data['numero_ordine'] . " (usare DD/MM/YYYY)");
                }
            }
            
            if (empty($scontrino_data['articoli']) || !is_array($scontrino_data['articoli'])) {
                throw new Exception("Nessun articolo per ordine: " . $scontrino_data['numero_ordine']);
            }
            
            // Calcola totali
            $totale_netto = $scontrino_data['totale'] ?? 0; // senza IVA
            $totale_lordo = $totale_netto * (1 + $iva_percentage); // con IVA
            $da_versare = $totale_lordo; // da versare uguale al lordo
            
            // Genera numero progressivo univoco per lo scontrino
            $stmt = $db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 3) AS UNSIGNED)), 0) + 1 AS next_number FROM scontrini WHERE numero LIKE 'SC%'");
            $next_number = $stmt->fetch()['next_number'];
            $numero_scontrino = 'SC' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
            
            // Inserisci lo scontrino
            $db->query("
                INSERT INTO scontrini (
                    numero, nome_persona, data, lordo, netto, da_versare, 
                    note, utente_id, filiale_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $numero_scontrino,
                $scontrino_data['nome'],
                $scontrino_data['data'],
                $totale_lordo,
                $totale_netto,
                $da_versare,
                "Importato da Excel - Ordine: " . $scontrino_data['numero_ordine'],
                $target_user_id,
                $target_filiale_id
            ]);
            
            $scontrino_id = $db->lastInsertId();
            
            // Inserisci dettagli articoli
            $numero_ordine_dettaglio = 1;
            foreach ($scontrino_data['articoli'] as $articolo) {
                try {
                    $db->query("
                        INSERT INTO scontrini_dettagli (
                            scontrino_id, numero_ordine, codice_articolo, 
                            descrizione_materiale, qta, prezzo_unitario, prezzo_totale,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ", [
                        $scontrino_id,
                        $numero_ordine_dettaglio++,
                        $articolo['codice_articolo'],
                        $articolo['descrizione_materiale'],
                        $articolo['qta'],
                        $articolo['prezzo_unitario'],
                        $articolo['prezzo_totale']
                    ]);
                    
                    $risultati['dettagli_creati']++;
                } catch (Exception $e) {
                    $risultati['errori'][] = "Errore inserimento articolo per ordine {$scontrino_data['numero_ordine']}: " . $e->getMessage();
                }
            }
            
            $risultati['scontrini_creati']++;
            $risultati['totale_importo'] += $totale_lordo; // Memorizza il totale con IVA
            $risultati['scontrini_ids'][$scontrino_id] = $numero_scontrino;
            
        } catch (Exception $e) {
            $risultati['errori'][] = "Errore scontrino {$scontrino_data['numero_ordine']}: " . $e->getMessage();
        }
    }
    
    // Commit transazione
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Importazione completata: {$risultati['scontrini_creati']} scontrini, {$risultati['dettagli_creati']} articoli",
        'risultati' => $risultati
    ]);
    
} catch (Exception $e) {
    // Rollback in caso di errore grave
    if ($db && $db->inTransaction()) {
        $db->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>