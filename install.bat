@echo off
echo.
echo ============================================
echo   Installazione Gestione Scontrini - Windows
echo ============================================
echo.

REM Colori
set "GREEN=[92m"
set "YELLOW=[93m"
set "RED=[91m"
set "BLUE=[94m"
set "NC=[0m"

set "SCRIPT_DIR=%~dp0"

echo %BLUE%[INFO]%NC% Avvio installazione Windows...

REM Rileva XAMPP
echo %BLUE%[INFO]%NC% Ricerca server web...

set "WEB_ROOT="
set "SERVER_TYPE="

if exist "C:\xampp\htdocs" (
    set "WEB_ROOT=C:\xampp\htdocs"
    set "SERVER_TYPE=XAMPP Windows"
    echo %GREEN%[OK]%NC% XAMPP rilevato: %WEB_ROOT%
) else if exist "C:\wamp64\www" (
    set "WEB_ROOT=C:\wamp64\www"  
    set "SERVER_TYPE=WAMP"
    echo %GREEN%[OK]%NC% WAMP rilevato: %WEB_ROOT%
) else if exist "C:\wamp\www" (
    set "WEB_ROOT=C:\wamp\www"
    set "SERVER_TYPE=WAMP"
    echo %GREEN%[OK]%NC% WAMP rilevato: %WEB_ROOT%
) else (
    echo %YELLOW%[WARNING]%NC% Server web non rilevato automaticamente
    set /p "WEB_ROOT=Inserisci percorso document root (es: C:\xampp\htdocs): "
    set "SERVER_TYPE=Custom"
)

REM Verifica PHP
php -v >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    for /f "tokens=2" %%a in ('php -v ^| findstr /C:"PHP"') do (
        echo %GREEN%[OK]%NC% PHP %%a trovato
    )
) else (
    echo %RED%[ERROR]%NC% PHP non trovato nel PATH
    echo Assicurati che XAMPP sia installato e PHP sia nel PATH
    pause
    exit /b 1
)

REM Verifica estensioni PHP
echo %BLUE%[INFO]%NC% Verifica estensioni PHP...

set "MISSING_EXT=0"
php -m | findstr /C:"pdo" >nul || (echo %RED%[ERROR]%NC% PDO mancante & set "MISSING_EXT=1")
php -m | findstr /C:"pdo_mysql" >nul || (echo %RED%[ERROR]%NC% PDO_MySQL mancante & set "MISSING_EXT=1")
php -m | findstr /C:"mysqli" >nul || (echo %RED%[ERROR]%NC% MySQLi mancante & set "MISSING_EXT=1")

if %MISSING_EXT% EQU 1 (
    echo %YELLOW%[WARNING]%NC% Alcune estensioni PHP mancanti
    echo Controlla il file php.ini in XAMPP
)

REM Menu installazione
echo.
echo %BLUE%[MENU]%NC% Tipo di installazione:
echo 1. Installazione nella cartella corrente
echo 2. Copia in %WEB_ROOT%\scontrini
echo 3. Cartella personalizzata
echo 4. Solo test sistema
echo 5. Esci
echo.

set /p "install_choice=Scegli opzione (1-5): "

if "%install_choice%"=="1" (
    set "INSTALL_DIR=%SCRIPT_DIR%"
    echo %BLUE%[INFO]%NC% Installazione in cartella corrente
) else if "%install_choice%"=="2" (
    set "INSTALL_DIR=%WEB_ROOT%\scontrini"
    echo %BLUE%[INFO]%NC% Installazione in %INSTALL_DIR%
) else if "%install_choice%"=="3" (
    set /p "INSTALL_DIR=Inserisci percorso installazione: "
    echo %BLUE%[INFO]%NC% Installazione in %INSTALL_DIR%
) else if "%install_choice%"=="4" (
    goto :test_only
) else if "%install_choice%"=="5" (
    exit /b 0
) else (
    echo %RED%[ERROR]%NC% Scelta non valida
    pause
    exit /b 1
)

REM Crea cartella se necessario
if not exist "%INSTALL_DIR%" (
    mkdir "%INSTALL_DIR%"
    echo %GREEN%[OK]%NC% Cartella creata: %INSTALL_DIR%
)

REM Copia file se necessario
if not "%SCRIPT_DIR%"=="%INSTALL_DIR%" (
    echo %BLUE%[INFO]%NC% Copia file in corso...
    
    xcopy "%SCRIPT_DIR%*" "%INSTALL_DIR%\" /E /I /H /Y >nul
    
    if %ERRORLEVEL% EQU 0 (
        echo %GREEN%[OK]%NC% File copiati in %INSTALL_DIR%
    ) else (
        echo %RED%[ERROR]%NC% Errore copia file
        pause
        exit /b 1
    )
)

REM Configurazione .htaccess
echo %BLUE%[INFO]%NC% Configurazione .htaccess...

if exist "%INSTALL_DIR%\.htaccess" (
    echo Quale .htaccess vuoi usare?
    echo 1. Standard (sicurezza avanzata)  
    echo 2. Semplice (compatibilità massima)
    echo 3. Disabilita .htaccess
    
    set /p "htaccess_choice=Scelta (1-3): "
    
    if "%htaccess_choice%"=="2" (
        if exist "%INSTALL_DIR%\.htaccess-simple" (
            move "%INSTALL_DIR%\.htaccess" "%INSTALL_DIR%\.htaccess-backup" >nul
            move "%INSTALL_DIR%\.htaccess-simple" "%INSTALL_DIR%\.htaccess" >nul
            echo %GREEN%[OK]%NC% .htaccess semplice attivato
        )
    ) else if "%htaccess_choice%"=="3" (
        move "%INSTALL_DIR%\.htaccess" "%INSTALL_DIR%\.htaccess-disabled" >nul
        echo %YELLOW%[WARNING]%NC% .htaccess disabilitato
    )
)

REM Configurazione database
echo.
echo %BLUE%[INFO]%NC% Configurazione database MySQL...
echo Inserisci i parametri del database:

set /p "db_host=Host (default localhost): "
if "%db_host%"=="" set "db_host=localhost"

set /p "db_name=Nome database (default scontrini_db): "
if "%db_name%"=="" set "db_name=scontrini_db"

set /p "db_user=Username (default root): " 
if "%db_user%"=="" set "db_user=root"

set /p "db_pass=Password (lascia vuoto per XAMPP): "

REM Test connessione database
echo %BLUE%[INFO]%NC% Test connessione database...

set "MYSQL_PATH="
if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe"

if defined MYSQL_PATH (
    "%MYSQL_PATH%" -h%db_host% -u%db_user% -p%db_pass% -e "SELECT 1;" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo %GREEN%[OK]%NC% Connessione database: OK
        
        REM Crea database se non esiste
        "%MYSQL_PATH%" -h%db_host% -u%db_user% -p%db_pass% -e "CREATE DATABASE IF NOT EXISTS %db_name% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
        
    ) else (
        echo %YELLOW%[WARNING]%NC% Problema connessione database
        echo Verifica che MySQL sia avviato in XAMPP
    )
) else (
    echo %YELLOW%[WARNING]%NC% MySQL client non trovato
)

REM Genera config.php
echo %BLUE%[INFO]%NC% Creazione config.php...

(
echo ^<?php
echo // Configurazione database
echo define^('DB_HOST', '%db_host%'^);
echo define^('DB_NAME', '%db_name%'^);
echo define^('DB_USER', '%db_user%'^);
echo define^('DB_PASS', '%db_pass%'^);
echo.
echo // Configurazione sessioni
echo define^('SESSION_LIFETIME', 1800^); // 30 minuti
echo define^('SESSION_SECRET', 'windows-secret-key-123'^);
echo.
echo // Configurazione generale
echo define^('SITE_NAME', 'Gestione Scontrini Fiscali'^);
echo define^('LOCALE', 'it_IT'^);
echo.
echo // Timezone
echo date_default_timezone_set^('Europe/Rome'^);
echo.
echo // Avvia sessione se non già attiva
echo if ^(session_status^(^) == PHP_SESSION_NONE^) {
echo     session_start^(^);
echo }
echo ?^>
) > "%INSTALL_DIR%\config.php"

echo %GREEN%[OK]%NC% config.php creato

REM Test finale
if exist "%INSTALL_DIR%\test.php" (
    echo %BLUE%[INFO]%NC% Test finale installazione...
    php "%INSTALL_DIR%\test.php" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo %GREEN%[OK]%NC% Test installazione: OK
    ) else (
        echo %YELLOW%[WARNING]%NC% Possibili problemi installazione
    )
)

goto :success

:test_only
echo %BLUE%[INFO]%NC% Solo test sistema...
echo Server: %SERVER_TYPE%
echo Web Root: %WEB_ROOT%
php -v | findstr /C:"PHP"
echo Test completato
pause
exit /b 0

:success
echo.
echo %GREEN%[SUCCESS]%NC% ================================
echo %GREEN%[SUCCESS]%NC%   Installazione completata!
echo %GREEN%[SUCCESS]%NC% ================================
echo.
echo %BLUE%[INFO]%NC% Prossimi passi:
echo 1. Avvia XAMPP (Apache + MySQL)

if not "%SCRIPT_DIR%"=="%INSTALL_DIR%" (
    echo 2. Vai su: http://localhost/scontrini/setup.php
) else (
    echo 2. Configura il virtual host per accedere all'app
)

echo 3. Completa la configurazione guidata
echo 4. Elimina setup.php dopo l'installazione
echo.
echo %BLUE%[INFO]%NC% Credenziali iniziali:
echo   Username: admin
echo   Password: admin123
echo.
echo File installati in: %INSTALL_DIR%
echo.

pause