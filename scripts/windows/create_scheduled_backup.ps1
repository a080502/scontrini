# Script per creare automatismo backup serale
# Esegui come Amministratore: powershell -ExecutionPolicy Bypass -File create_scheduled_backup.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Setup Backup Automatico Serale" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verifica privilegi amministratore
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator"))
{
    Write-Host "[ERROR] Questo script deve essere eseguito come Amministratore!" -ForegroundColor Red
    Write-Host "Clicca destro su PowerShell e seleziona 'Esegui come amministratore'" -ForegroundColor Yellow
    Read-Host "Premi Enter per uscire"
    exit 1
}

# Percorsi
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$BackupScript = "$ScriptPath\backup.ps1"

# Verifica che esista il backup script
if (!(Test-Path $BackupScript)) {
    Write-Host "[ERROR] Script backup.ps1 non trovato in: $BackupScript" -ForegroundColor Red
    Read-Host "Premi Enter per uscire"
    exit 1
}

Write-Host "[INFO] Script backup trovato: $BackupScript" -ForegroundColor Blue

# Configura orario
$DefaultTime = "22:00"
$BackupTime = Read-Host "Inserisci orario backup (formato HH:MM, default $DefaultTime)"
if ([string]::IsNullOrEmpty($BackupTime)) {
    $BackupTime = $DefaultTime
}

Write-Host "[INFO] Orario backup configurato: $BackupTime" -ForegroundColor Blue

try {
    # Crea task schedulato
    $TaskName = "Backup Scontrini Serale"
    $TaskDescription = "Backup automatico database e file applicazione Gestione Scontrini PHP"
    
    # Elimina task esistente se presente
    $ExistingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($ExistingTask) {
        Write-Host "[INFO] Task esistente trovato, lo sostituisco..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    }
    
    # Crea azione
    $Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$BackupScript`""
    
    # Crea trigger (ogni giorno all'orario specificato)
    $Trigger = New-ScheduledTaskTrigger -Daily -At $BackupTime
    
    # Crea impostazioni
    $Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    
    # Crea task
    Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Description $TaskDescription -User "SYSTEM"
    
    Write-Host ""
    Write-Host "[OK] Task schedulato creato con successo!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Dettagli configurazione:" -ForegroundColor Cyan
    Write-Host "- Nome task: $TaskName" -ForegroundColor Gray
    Write-Host "- Orario: $BackupTime ogni giorno" -ForegroundColor Gray
    Write-Host "- Script: $BackupScript" -ForegroundColor Gray
    Write-Host "- Utente: SYSTEM (amministratore)" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Il backup verrà eseguito automaticamente ogni sera alle $BackupTime" -ForegroundColor Green
    Write-Host "I backup saranno salvati in: $env:USERPROFILE\scontrini_backup" -ForegroundColor Green
    Write-Host ""
    Write-Host "Per gestire il task:" -ForegroundColor Yellow
    Write-Host "1. Apri 'Utilità di pianificazione' (Task Scheduler)" -ForegroundColor Gray
    Write-Host "2. Vai in 'Libreria Utilità di pianificazione'" -ForegroundColor Gray
    Write-Host "3. Cerca '$TaskName'" -ForegroundColor Gray
    
} catch {
    Write-Host ""
    Write-Host "[ERROR] Errore durante creazione task: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Prova a:" -ForegroundColor Yellow
    Write-Host "1. Verificare di aver eseguito PowerShell come Amministratore" -ForegroundColor Gray
    Write-Host "2. Controllare che il percorso del backup script sia corretto" -ForegroundColor Gray
    Write-Host "3. Creare il task manualmente tramite Utilità di pianificazione" -ForegroundColor Gray
}

# Test opzionale
Write-Host ""
$TestRun = Read-Host "Vuoi testare il backup ora? (s/N)"
if ($TestRun -eq "s" -or $TestRun -eq "S") {
    Write-Host "[INFO] Avvio test backup..." -ForegroundColor Blue
    & $BackupScript
}

Write-Host ""
Read-Host "Premi Enter per uscire"