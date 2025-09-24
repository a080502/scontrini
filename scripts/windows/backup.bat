@echo off
echo.
echo ===========================================
echo   Backup Gestione Scontrini PHP - Windows
echo ===========================================
echo.

REM Colori per Windows (limitati)
set "GREEN=[92m"
set "YELLOW=[93m"
set "RED=[91m"
set "BLUE=[94m"
set "NC=[0m"

REM Configurazione
set "SCRIPT_DIR=%~dp0"
set "BACKUP_DIR=%USERPROFILE%\scontrini_backup"
set "DATE=%date:~6,4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set "DATE=%DATE: =0%"
set "BACKUP_NAME=scontrini_backup_%DATE%"

echo %BLUE%[INFO]%NC% Avvio backup sistema...

REM Crea cartella backup
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
if not exist "%BACKUP_DIR%\%BACKUP_NAME%" mkdir "%BACKUP_DIR%\%BACKUP_NAME%"

echo %GREEN%[OK]%NC% Cartella backup creata: %BACKUP_DIR%\%BACKUP_NAME%

REM Leggi configurazione database da config.php
echo %BLUE%[INFO]%NC% Lettura configurazione database...

if not exist "%SCRIPT_DIR%..\..\config.php" (
    echo %RED%[ERROR]%NC% File config.php non trovato!
    pause
    exit /b 1
)

REM Estrai parametri database (metodo migliorato per Windows)
for /f "tokens=4 delims='" %%a in ('findstr "DB_HOST" "%SCRIPT_DIR%..\..\config.php"') do set "DB_HOST=%%a"
for /f "tokens=4 delims='" %%a in ('findstr "DB_NAME" "%SCRIPT_DIR%..\..\config.php"') do set "DB_NAME=%%a"
for /f "tokens=4 delims='" %%a in ('findstr "DB_USER" "%SCRIPT_DIR%..\..\config.php"') do set "DB_USER=%%a"
for /f "tokens=4 delims='" %%a in ('findstr "DB_PASS" "%SCRIPT_DIR%..\..\config.php"') do set "DB_PASS=%%a"

REM Verifica che i valori siano stati letti
if not defined DB_HOST (
    echo %RED%[ERROR]%NC% Impossibile leggere DB_HOST da config.php
    pause
    exit /b 1
)

echo %GREEN%[OK]%NC% Configurazione database caricata

REM Backup file applicazione
echo %BLUE%[INFO]%NC% Backup file applicazione...

REM Usa PowerShell per creare archivio (disponibile su Windows 7+)
powershell -command "Compress-Archive -Path '%SCRIPT_DIR%..\..\*' -DestinationPath '%BACKUP_DIR%\%BACKUP_NAME%\files.zip' -Force -CompressionLevel Optimal"

if %ERRORLEVEL% EQU 0 (
    echo %GREEN%[OK]%NC% File applicazione salvati
) else (
    echo %YELLOW%[WARNING]%NC% Problemi con backup file
)

REM Backup database
echo %BLUE%[INFO]%NC% Backup database...

REM Cerca mysqldump in varie posizioni XAMPP
set "MYSQLDUMP_PATH="
if exist "C:\xampp\mysql\bin\mysqldump.exe" set "MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe"
if exist "C:\xampp\mysql\bin\mysqldump.exe" set "MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe"
if exist "%PROGRAMFILES%\MySQL\MySQL Server 8.0\bin\mysqldump.exe" set "MYSQLDUMP_PATH=%PROGRAMFILES%\MySQL\MySQL Server 8.0\bin\mysqldump.exe"

if not defined MYSQLDUMP_PATH (
    echo %YELLOW%[WARNING]%NC% mysqldump non trovato, backup database saltato
    goto :skip_database
)

REM Backup completo database (versione alternativa con file SQL)
echo %BLUE%[INFO]%NC% Creazione backup database...

REM Crea file SQL temporaneo per evitare problemi con nome database
echo SHOW DATABASES; > "%BACKUP_DIR%\%BACKUP_NAME%\temp_dump.sql"

REM Prova approccio 1: Variabile separata
set "TARGET_DB=scontrini_db"
if "%DB_PASS%"=="" (
    "%MYSQLDUMP_PATH%" -hlocalhost -uroot %TARGET_DB% > "%BACKUP_DIR%\%BACKUP_NAME%\database.sql" 2>"%BACKUP_DIR%\%BACKUP_NAME%\backup_error.log"
) else (
    "%MYSQLDUMP_PATH%" -hlocalhost -uroot -p%DB_PASS% %TARGET_DB% > "%BACKUP_DIR%\%BACKUP_NAME%\database.sql" 2>"%BACKUP_DIR%\%BACKUP_NAME%\backup_error.log"
)

REM Se fallisce, prova approccio 2: File batch separato
if %ERRORLEVEL% NEQ 0 (
    echo %YELLOW%[WARNING]%NC% Primo tentativo fallito, provo metodo alternativo...
    
    REM Crea mini script per mysqldump
    echo @echo off > "%BACKUP_DIR%\%BACKUP_NAME%\dump_cmd.bat"
    if "%DB_PASS%"=="" (
        echo "%MYSQLDUMP_PATH%" -hlocalhost -uroot scontrini_db >> "%BACKUP_DIR%\%BACKUP_NAME%\dump_cmd.bat"
    ) else (
        echo "%MYSQLDUMP_PATH%" -hlocalhost -uroot -p%DB_PASS% scontrini_db >> "%BACKUP_DIR%\%BACKUP_NAME%\dump_cmd.bat"
    )
    
    REM Esegui script separato
    call "%BACKUP_DIR%\%BACKUP_NAME%\dump_cmd.bat" > "%BACKUP_DIR%\%BACKUP_NAME%\database.sql" 2>"%BACKUP_DIR%\%BACKUP_NAME%\backup_error.log"
    
    REM Pulizia file temporaneo
    del "%BACKUP_DIR%\%BACKUP_NAME%\dump_cmd.bat" 2>nul
)

if %ERRORLEVEL% EQU 0 (
    echo %GREEN%[OK]%NC% Database salvato
    
    REM Verifica se il file è stato creato e ha contenuto
    if exist "%BACKUP_DIR%\%BACKUP_NAME%\database.sql" (
        for %%I in ("%BACKUP_DIR%\%BACKUP_NAME%\database.sql") do (
            if %%~zI gtr 0 (
                echo %GREEN%[OK]%NC% File database.sql creato (%%~zI bytes)
            ) else (
                echo %YELLOW%[WARNING]%NC% File database.sql vuoto
            )
        )
    )
    
    REM Backup struttura database (solo tabelle)
    if "%DB_PASS%"=="" (
        "%MYSQLDUMP_PATH%" -hlocalhost -uroot --no-data %TARGET_DB% > "%BACKUP_DIR%\%BACKUP_NAME%\database_structure.sql"
    ) else (
        "%MYSQLDUMP_PATH%" -hlocalhost -uroot -p%DB_PASS% --no-data %TARGET_DB% > "%BACKUP_DIR%\%BACKUP_NAME%\database_structure.sql"
    )
    
    REM Backup dati database (solo dati)
    if "%DB_PASS%"=="" (
        "%MYSQLDUMP_PATH%" -hlocalhost -uroot --no-create-info %TARGET_DB% > "%BACKUP_DIR%\%BACKUP_NAME%\database_data.sql"
    ) else (
        "%MYSQLDUMP_PATH%" -hlocalhost -uroot -p%DB_PASS% --no-create-info %TARGET_DB% > "%BACKUP_DIR%\%BACKUP_NAME%\database_data.sql"
    )
    
    echo %GREEN%[OK]%NC% Backup database completo
) else (
    echo %YELLOW%[WARNING]%NC% Problemi backup database
    REM Mostra errori se esistono
    if exist "%BACKUP_DIR%\%BACKUP_NAME%\backup_error.log" (
        echo %RED%[ERROR]%NC% Dettagli errore:
        type "%BACKUP_DIR%\%BACKUP_NAME%\backup_error.log"
    )
)

:skip_database

REM Crea file info backup
echo Backup Gestione Scontrini PHP - Windows > "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo ========================================= >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo. >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo Data backup: %date% %time% >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo Sistema: Windows >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo. >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo Database: >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - Host: %DB_HOST% >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - Nome: %DB_NAME% >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - User: %DB_USER% >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo. >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo File inclusi: >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - files.zip: Tutti i file dell'applicazione >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - database.sql: Backup completo database >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - database_structure.sql: Solo struttura database >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"
echo - database_data.sql: Solo dati database >> "%BACKUP_DIR%\%BACKUP_NAME%\backup_info.txt"

echo %GREEN%[OK]%NC% File info creato

REM Comprimi tutto in un unico archivio
echo %BLUE%[INFO]%NC% Compressione finale...

powershell -command "Compress-Archive -Path '%BACKUP_DIR%\%BACKUP_NAME%\*' -DestinationPath '%BACKUP_DIR%\%BACKUP_NAME%.zip' -Force -CompressionLevel Optimal"

if %ERRORLEVEL% EQU 0 (
    rmdir /s /q "%BACKUP_DIR%\%BACKUP_NAME%"
    echo %GREEN%[OK]%NC% Backup completato: %BACKUP_DIR%\%BACKUP_NAME%.zip
) else (
    echo %YELLOW%[WARNING]%NC% Problemi compressione finale
)

REM Statistiche
for %%A in ("%BACKUP_DIR%\%BACKUP_NAME%.zip") do (
    echo %BLUE%[INFO]%NC% Dimensione backup: %%~zA bytes
)

REM Pulizia backup vecchi (opzionale)
echo.
set /p cleanup="Vuoi eliminare i backup piu' vecchi di 30 giorni? (s/N): "
if /i "%cleanup%"=="s" (
    echo %BLUE%[INFO]%NC% Eliminazione backup vecchi...
    
    REM Elimina file .zip più vecchi di 30 giorni
    forfiles /p "%BACKUP_DIR%" /s /m "scontrini_backup_*.zip" /d -30 /c "cmd /c del @path" 2>nul
    
    echo %GREEN%[OK]%NC% Backup vecchi eliminati
)

echo.
echo %GREEN%[SUCCESS]%NC% ===========================
echo %GREEN%[SUCCESS]%NC%   Backup completato!
echo %GREEN%[SUCCESS]%NC% ===========================
echo.
echo Percorso: %BACKUP_DIR%\%BACKUP_NAME%.zip
echo Per ripristinare: usa restore.bat o estrai manualmente
echo.

pause