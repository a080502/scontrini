// Mobile Detection Enhancement
// Questo script migliora il rilevamento dei dispositivi mobili
// aggiungendo controlli lato client per larghezza schermo e touch support

(function() {
    'use strict';
    
    function isMobileDevice() {
        // Controlla se il dispositivo supporta il touch
        const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        // Controlla la larghezza dello schermo
        const isMobileScreen = window.innerWidth <= 768;
        
        // Controlla l'orientazione (se supportata)
        const isPortrait = window.screen && window.screen.orientation 
            ? window.screen.orientation.angle === 0 || window.screen.orientation.angle === 180
            : window.innerHeight > window.innerWidth;
        
        // Combina i vari controlli
        return hasTouch && (isMobileScreen || isPortrait);
    }
    
    function redirectToMobileVersion() {
        // Solo se siamo sulla pagina aggiungi.php e non è già stato forzato il desktop
        if (window.location.pathname.includes('aggiungi.php') && 
            !window.location.search.includes('force_desktop') &&
            isMobileDevice()) {
            
            window.location.href = 'aggiungi-mobile.php';
        }
    }
    
    function redirectToDesktopVersion() {
        // Solo se siamo sulla pagina aggiungi-mobile.php e non è un dispositivo mobile
        if (window.location.pathname.includes('aggiungi-mobile.php') && 
            !window.location.search.includes('force_mobile') &&
            !isMobileDevice()) {
            
            window.location.href = 'aggiungi.php';
        }
    }
    
    // Esegui il controllo al caricamento della pagina
    document.addEventListener('DOMContentLoaded', function() {
        redirectToMobileVersion();
        redirectToDesktopVersion();
    });
    
    // Esegui anche il controllo quando cambia l'orientazione del dispositivo
    window.addEventListener('orientationchange', function() {
        // Aspetta un po' che l'orientazione si stabilizzi
        setTimeout(function() {
            redirectToMobileVersion();
            redirectToDesktopVersion();
        }, 100);
    });
    
    // Esegui il controllo quando cambia la dimensione della finestra
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            redirectToMobileVersion();
            redirectToDesktopVersion();
        }, 250);
    });
    
})();