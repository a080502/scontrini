<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';
require_once '../includes/scontrino_dettagli.php';

// Verifica autenticazione
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Autenticazione richiesta']);
    exit;
}

$action = $_GET['action'] ?? '';
$dettagli = new ScontrinoDettagli();

try {
    switch ($action) {
        case 'get':
            $scontrino_id = intval($_GET['scontrino_id'] ?? 0);
            if ($scontrino_id <= 0) {
                throw new Exception('ID scontrino non valido');
            }
            
            $dettagli_scontrino = $dettagli->getDettagliScontrino($scontrino_id);
            $totali = $dettagli->calcolaTotaleDettagli($scontrino_id);
            
            echo json_encode([
                'success' => true,
                'dettagli' => $dettagli_scontrino,
                'totali' => $totali
            ]);
            break;
            
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $scontrino_id = intval($data['scontrino_id'] ?? 0);
            
            if ($scontrino_id <= 0) {
                throw new Exception('ID scontrino non valido');
            }
            
            // Validazione dati
            if (empty($data['descrizione_materiale'])) {
                throw new Exception('Descrizione materiale obbligatoria');
            }
            
            if (!is_numeric($data['qta']) || $data['qta'] <= 0) {
                throw new Exception('Quantità deve essere un numero positivo');
            }
            
            if (!is_numeric($data['prezzo_unitario']) || $data['prezzo_unitario'] < 0) {
                throw new Exception('Prezzo unitario deve essere un numero >= 0');
            }
            
            $result = $dettagli->aggiungiDettaglio($scontrino_id, $data);
            
            if ($result) {
                $nuovi_dettagli = $dettagli->getDettagliScontrino($scontrino_id);
                $totali = $dettagli->calcolaTotaleDettagli($scontrino_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Dettaglio aggiunto con successo',
                    'dettagli' => $nuovi_dettagli,
                    'totali' => $totali
                ]);
            } else {
                throw new Exception('Errore nell\'aggiunta del dettaglio');
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID dettaglio non valido');
            }
            
            // Validazione dati
            if (empty($data['descrizione_materiale'])) {
                throw new Exception('Descrizione materiale obbligatoria');
            }
            
            if (!is_numeric($data['qta']) || $data['qta'] <= 0) {
                throw new Exception('Quantità deve essere un numero positivo');
            }
            
            if (!is_numeric($data['prezzo_unitario']) || $data['prezzo_unitario'] < 0) {
                throw new Exception('Prezzo unitario deve essere un numero >= 0');
            }
            
            $result = $dettagli->aggiornaDettaglio($id, $data);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dettaglio aggiornato con successo'
                ]);
            } else {
                throw new Exception('Errore nell\'aggiornamento del dettaglio');
            }
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $scontrino_id = intval($data['scontrino_id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID dettaglio non valido');
            }
            
            $result = $dettagli->eliminaDettaglio($id);
            
            if ($result && $scontrino_id > 0) {
                // Riordina i dettagli rimanenti
                $dettagli->riordinaDettagli($scontrino_id);
                
                $nuovi_dettagli = $dettagli->getDettagliScontrino($scontrino_id);
                $totali = $dettagli->calcolaTotaleDettagli($scontrino_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Dettaglio eliminato con successo',
                    'dettagli' => $nuovi_dettagli,
                    'totali' => $totali
                ]);
            } else {
                throw new Exception('Errore nell\'eliminazione del dettaglio');
            }
            break;
            
        case 'search_articoli':
            $query = $_GET['q'] ?? '';
            $limit = intval($_GET['limit'] ?? 10);
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'articoli' => []]);
                break;
            }
            
            $articoli = $dettagli->cercaArticoli($query, $limit);
            
            echo json_encode([
                'success' => true,
                'articoli' => $articoli
            ]);
            break;
            
        case 'import_excel':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $scontrino_id = intval($data['scontrino_id'] ?? 0);
            $dettagli_array = $data['dettagli'] ?? [];
            
            if ($scontrino_id <= 0) {
                throw new Exception('ID scontrino non valido');
            }
            
            if (empty($dettagli_array)) {
                throw new Exception('Nessun dettaglio da importare');
            }
            
            $risultato = $dettagli->importaDettagliDaArray($scontrino_id, $dettagli_array);
            
            $nuovi_dettagli = $dettagli->getDettagliScontrino($scontrino_id);
            $totali = $dettagli->calcolaTotaleDettagli($scontrino_id);
            
            echo json_encode([
                'success' => true,
                'message' => "Importazione completata: {$risultato['successi']} successi, " . count($risultato['errori']) . " errori",
                'risultato' => $risultato,
                'dettagli' => $nuovi_dettagli,
                'totali' => $totali
            ]);
            break;
            
        default:
            throw new Exception('Azione non valida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>