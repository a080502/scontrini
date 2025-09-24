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

REM Estrai parametri database (metodo semplificato per Windows)
for /f "tokens=2 delims='" %%a in ('findstr "DB_HOST" "%SCRIPT_DIR%..\..\config.php"') do set "DB_HOST=%%a"
for /f "tokens=2 delims='" %%a in ('findstr "DB_NAME" "%SCRIPT_DIR%..\..\config.php"') do set "DB_NAME=%%a"
for /f "tokens=2 delims='" %%a in ('findstr "DB_USER" "%SCRIPT_DIR%..\..\config.php"') do set "DB_USER=%%a"
for /f "tokens=2 delims='" %%a in ('findstr "DB_PASS" "%SCRIPT_DIR%..\..\config.php"') do set "DB_PASS=%%a"

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

REM Backup completo database
if "%DB_PASS%"=="" (
    "%MYSQLDUMP_PATH%" -h%DB_HOST% -u%DB_USER% --routines --triggers --single-transaction %DB_NAME% > "%BACKUP_DIR%\%BACKUP_NAME%\database.sql"
) else (
    "%MYSQLDUMP_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% --routines --triggers --single-transaction %DB_NAME% > "%BACKUP_DIR%\%BACKUP_NAME%\database.sql"
)

if %ERRORLEVEL% EQU 0 (
    echo %GREEN%[OK]%NC% Database salvato
    
    REM Backup struttura database
    if "%DB_PASS%"=="" (
        "%MYSQLDUMP_PATH%" -h%DB_HOST% -u%DB_USER% --no-data --routines --triggers %DB_NAME% > "%BACKUP_DIR%\%BACKUP_NAME%\database_structure.sql"
    ) else (
        "%MYSQLDUMP_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% --no-data --routines --triggers %DB_NAME% > "%BACKUP_DIR%\%BACKUP_NAME%\database_structure.sql"
    )
    
    REM Backup dati database
    if "%DB_PASS%"=="" (
        "%MYSQLDUMP_PATH%" -h%DB_HOST% -u%DB_USER% --no-create-info --skip-triggers %DB_NAME% > "%BACKUP_DIR%\%BACKUP_NAME%\database_data.sql"
    ) else (
        "%MYSQLDUMP_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% --no-create-info --skip-triggers %DB_NAME% > "%BACKUP_DIR%\%BACKUP_NAME%\database_data.sql"
    )
) else (
    echo %YELLOW%[WARNING]%NC% Problemi backup database
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