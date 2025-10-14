@echo off
echo.
echo =========================================
echo   Setup Backup Automatico Serale
echo =========================================
echo.

REM Verifica se è amministratore
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo [ERROR] Questo script deve essere eseguito come Amministratore!
    echo.
    echo Clicca destro su questo file e seleziona "Esegui come amministratore"
    echo.
    pause
    exit /b 1
)

echo [INFO] Avvio configurazione automatismo backup...
echo.

REM Esegui script PowerShell per creare task
powershell -ExecutionPolicy Bypass -File "%~dp0create_scheduled_backup.ps1"

echo.
echo [INFO] Configurazione completata
pause