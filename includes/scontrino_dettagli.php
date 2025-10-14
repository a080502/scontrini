<?php
/**
 * Classe per la gestione dei dettagli articoli degli scontrini
 */

class ScontrinoDettagli {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Recupera tutti i dettagli di uno scontrino
     */
    public function getDettagliScontrino($scontrino_id) {
        return $this->db->fetchAll("
            SELECT * FROM scontrini_dettagli 
            WHERE scontrino_id = ? 
            ORDER BY numero_ordine ASC
        ", [$scontrino_id]);
    }
    
    /**
     * Aggiunge un nuovo dettaglio allo scontrino
     */
    public function aggiungiDettaglio($scontrino_id, $dettaglio) {
        // Calcola automaticamente il numero d'ordine
        $ultimo_ordine = $this->db->fetchOne("
            SELECT COALESCE(MAX(numero_ordine), 0) as max_ordine 
            FROM scontrini_dettagli 
            WHERE scontrino_id = ?
        ", [$scontrino_id])['max_ordine'];
        
        $numero_ordine = $ultimo_ordine + 1;
        
        // Calcola prezzo totale
        $qta = floatval($dettaglio['qta']);
        $prezzo_unitario = floatval($dettaglio['prezzo_unitario']);
        $prezzo_totale = $qta * $prezzo_unitario;
        
        return $this->db->query("
            INSERT INTO scontrini_dettagli (
                scontrino_id, numero_ordine, codice_articolo, 
                descrizione_materiale, qta, prezzo_unitario, prezzo_totale
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $scontrino_id,
            $numero_ordine,
            $dettaglio['codice_articolo'] ?? null,
            $dettaglio['descrizione_materiale'],
            $qta,
            $prezzo_unitario,
            $prezzo_totale
        ]);
    }
    
    /**
     * Aggiorna un dettaglio esistente
     */
    public function aggiornaDettaglio($id, $dettaglio) {
        // Calcola prezzo totale
        $qta = floatval($dettaglio['qta']);
        $prezzo_unitario = floatval($dettaglio['prezzo_unitario']);
        $prezzo_totale = $qta * $prezzo_unitario;
        
        return $this->db->query("
            UPDATE scontrini_dettagli 
            SET codice_articolo = ?, descrizione_materiale = ?, 
                qta = ?, prezzo_unitario = ?, prezzo_totale = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $dettaglio['codice_articolo'] ?? null,
            $dettaglio['descrizione_materiale'],
            $qta,
            $prezzo_unitario,
            $prezzo_totale,
            $id
        ]);
    }
    
    /**
     * Elimina un dettaglio
     */
    public function eliminaDettaglio($id) {
        return $this->db->query("DELETE FROM scontrini_dettagli WHERE id = ?", [$id]);
    }
    
    /**
     * Riordina i dettagli dopo eliminazione
     */
    public function riordinaDettagli($scontrino_id) {
        $dettagli = $this->getDettagliScontrino($scontrino_id);
        
        foreach ($dettagli as $index => $dettaglio) {
            $nuovo_ordine = $index + 1;
            if ($dettaglio['numero_ordine'] != $nuovo_ordine) {
                $this->db->query("
                    UPDATE scontrini_dettagli 
                    SET numero_ordine = ? 
                    WHERE id = ?
                ", [$nuovo_ordine, $dettaglio['id']]);
            }
        }
    }
    
    /**
     * Calcola il totale di tutti i dettagli
     */
    public function calcolaTotaleDettagli($scontrino_id) {
        $result = $this->db->fetchOne("
            SELECT 
                SUM(prezzo_totale) as totale,
                COUNT(*) as num_articoli,
                SUM(qta) as qta_totale
            FROM scontrini_dettagli 
            WHERE scontrino_id = ?
        ", [$scontrino_id]);
        
        return [
            'totale' => floatval($result['totale'] ?? 0),
            'num_articoli' => intval($result['num_articoli'] ?? 0),
            'qta_totale' => floatval($result['qta_totale'] ?? 0)
        ];
    }
    
    /**
     * Elimina tutti i dettagli di uno scontrino
     */
    public function eliminaTuttiDettagli($scontrino_id) {
        return $this->db->query("DELETE FROM scontrini_dettagli WHERE scontrino_id = ?", [$scontrino_id]);
    }
    
    /**
     * Importa dettagli da array (per Excel import)
     */
    public function importaDettagliDaArray($scontrino_id, $dettagli_array) {
        // Prima elimina dettagli esistenti
        $this->eliminaTuttiDettagli($scontrino_id);
        
        $successi = 0;
        $errori = [];
        
        foreach ($dettagli_array as $index => $dettaglio) {
            try {
                // Validazione base
                if (empty($dettaglio['descrizione_materiale'])) {
                    $errori[] = "Riga " . ($index + 1) . ": Descrizione materiale obbligatoria";
                    continue;
                }
                
                if (!is_numeric($dettaglio['qta']) || $dettaglio['qta'] <= 0) {
                    $errori[] = "Riga " . ($index + 1) . ": Quantità deve essere un numero positivo";
                    continue;
                }
                
                if (!is_numeric($dettaglio['prezzo_unitario']) || $dettaglio['prezzo_unitario'] < 0) {
                    $errori[] = "Riga " . ($index + 1) . ": Prezzo unitario deve essere un numero >= 0";
                    continue;
                }
                
                // Forza numero d'ordine sequenziale
                $dettaglio_normalizzato = [
                    'codice_articolo' => $dettaglio['codice_articolo'] ?? null,
                    'descrizione_materiale' => $dettaglio['descrizione_materiale'],
                    'qta' => floatval($dettaglio['qta']),
                    'prezzo_unitario' => floatval($dettaglio['prezzo_unitario'])
                ];
                
                $this->aggiungiDettaglio($scontrino_id, $dettaglio_normalizzato);
                $successi++;
                
            } catch (Exception $e) {
                $errori[] = "Riga " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return [
            'successi' => $successi,
            'errori' => $errori,
            'totale_righe' => count($dettagli_array)
        ];
    }
    
    /**
     * Cerca articoli esistenti per autocomplete
     */
    public function cercaArticoli($query, $limit = 10) {
        $search = "%{$query}%";
        
        return $this->db->fetchAll("
            SELECT DISTINCT 
                codice_articolo,
                descrizione_materiale,
                AVG(prezzo_unitario) as prezzo_medio,
                COUNT(*) as utilizzi
            FROM scontrini_dettagli 
            WHERE (codice_articolo LIKE ? OR descrizione_materiale LIKE ?)
                AND codice_articolo IS NOT NULL
            GROUP BY codice_articolo, descrizione_materiale
            ORDER BY utilizzi DESC, prezzo_medio DESC
            LIMIT ?
        ", [$search, $search, $limit]);
    }
}
?>