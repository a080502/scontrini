<?php
/**
 * Script di aggiornamento batch per sostituire i riferimenti al numero con nome_display
 * nei messaggi di successo e nelle query
 */

$files_to_update = [
    'versa.php' => 'versato',
    'archivia.php' => 'archiviato', 
    'riattiva.php' => 'riattivato',
    'annulla_incasso.php' => 'incasso annullato',
    'annulla_versamento.php' => 'versamento annullato',
    'elimina.php' => 'eliminato'
];

foreach ($files_to_update as $file => $action) {
    if (file_exists($file)) {
        echo "📝 Aggiornamento $file...\n";
        
        $content = file_get_contents($file);
        
        // 1. Aggiorna query SELECT per includere nome_display
        $content = preg_replace(
            '/SELECT \* FROM scontrini WHERE id = \?/',
            'SELECT *, COALESCE(nome_persona, numero) as nome_display FROM scontrini WHERE id = ?',
            $content
        );
        
        // 2. Sostituisci numero con nome_display nei messaggi
        $content = str_replace(
            "scontrino['numero']",
            "scontrino['nome_display']",
            $content
        );
        
        // 3. Sostituisci numero nei messaggi HTML
        $content = str_replace(
            'htmlspecialchars($scontrino[\'numero\'])',
            'htmlspecialchars($scontrino[\'nome_display\'] ?? $scontrino[\'numero\'])',
            $content
        );
        
        file_put_contents($file, $content);
        echo "✅ $file aggiornato\n";
    } else {
        echo "⚠️ $file non trovato\n";
    }
}

echo "\n🎉 Aggiornamento completato!\n";
echo "📋 File aggiornati per usare nome_persona invece di numero\n";
?>