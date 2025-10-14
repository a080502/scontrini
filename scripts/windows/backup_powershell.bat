@echo off
echo.
echo ===========================================
echo   Backup Gestione Scontrini PHP - Windows
echo ===========================================
echo.

REM Controlla se PowerShell è disponibile
powershell -Command "Get-Host" >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PowerShell non disponibile su questo sistema
    echo Usa backup.bat come alternativa
    pause
    exit /b 1
)

REM Esegui script PowerShell
echo [INFO] Avvio backup tramite PowerShell...
powershell -ExecutionPolicy Bypass -File "%~dp0backup.ps1"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [SUCCESS] Backup PowerShell completato con successo!
) else (
    echo.
    echo [WARNING] Backup PowerShell terminato con errori
    echo Prova ad usare backup.bat come alternativa
)

echo.
pause