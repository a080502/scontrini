# Backup Gestione Scontrini PHP - Windows PowerShell
# =================================================

Write-Host ""
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host "  Backup Gestione Scontrini PHP - Windows" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""

# Configurazione
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$BackupDir = "$env:USERPROFILE\scontrini_backup"
$Date = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupName = "scontrini_backup_$Date"
$BackupPath = "$BackupDir\$BackupName"

Write-Host "[INFO] Avvio backup sistema..." -ForegroundColor Blue

# Crea cartella backup
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}
if (!(Test-Path $BackupPath)) {
    New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
}

Write-Host "[OK] Cartella backup creata: $BackupPath" -ForegroundColor Green

# Leggi configurazione database da config.php
Write-Host "[INFO] Lettura configurazione database..." -ForegroundColor Blue

$ConfigPath = "$ScriptDir\..\..\config.php"
if (!(Test-Path $ConfigPath)) {
    Write-Host "[ERROR] File config.php non trovato!" -ForegroundColor Red
    Read-Host "Premi Enter per continuare"
    exit 1
}

try {
    $ConfigContent = Get-Content $ConfigPath -Raw
    
    # Estrai parametri database usando regex
    $DbHost = [regex]::Match($ConfigContent, "define\('DB_HOST',\s*'([^']+)'\)").Groups[1].Value
    $DbName = [regex]::Match($ConfigContent, "define\('DB_NAME',\s*'([^']+)'\)").Groups[1].Value
    $DbUser = [regex]::Match($ConfigContent, "define\('DB_USER',\s*'([^']+)'\)").Groups[1].Value
    $DbPass = [regex]::Match($ConfigContent, "define\('DB_PASS',\s*'([^']*)'\)").Groups[1].Value
    
    if ([string]::IsNullOrEmpty($DbHost) -or [string]::IsNullOrEmpty($DbName) -or [string]::IsNullOrEmpty($DbUser)) {
        throw "Parametri database non trovati nel file config.php"
    }
    
    Write-Host "[OK] Configurazione database caricata" -ForegroundColor Green
    Write-Host "     Host: $DbHost" -ForegroundColor Gray
    Write-Host "     Database: $DbName" -ForegroundColor Gray
    Write-Host "     User: $DbUser" -ForegroundColor Gray
} catch {
    Write-Host "[ERROR] Errore lettura config.php: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "Premi Enter per continuare"
    exit 1
}

# Backup file applicazione
Write-Host "[INFO] Backup file applicazione..." -ForegroundColor Blue

try {
    $SourcePath = "$ScriptDir\..\.."
    $FilesZip = "$BackupPath\files.zip"
    
    # Usa Compress-Archive per creare l'archivio
    Compress-Archive -Path "$SourcePath\*" -DestinationPath $FilesZip -Force -CompressionLevel Optimal
    
    Write-Host "[OK] File applicazione salvati" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Problemi con backup file: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Backup database
Write-Host "[INFO] Backup database..." -ForegroundColor Blue

# Cerca mysqldump in varie posizioni
$MysqldumpPaths = @(
    "C:\xampp\mysql\bin\mysqldump.exe",
    "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
    "C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe",
    "C:\MySQL\bin\mysqldump.exe"
)

$MysqldumpPath = $null
foreach ($Path in $MysqldumpPaths) {
    if (Test-Path $Path) {
        $MysqldumpPath = $Path
        break
    }
}

if ($null -eq $MysqldumpPath) {
    Write-Host "[WARNING] mysqldump non trovato, backup database saltato" -ForegroundColor Yellow
} else {
    Write-Host "[INFO] Usando mysqldump: $MysqldumpPath" -ForegroundColor Blue
    
    try {
        # Costruisci il comando mysqldump
        $MysqldumpArgs = @(
            "-h$DbHost",
            "-u$DbUser"
        )
        
        if (![string]::IsNullOrEmpty($DbPass)) {
            $MysqldumpArgs += "-p$DbPass"
        }
        
        $MysqldumpArgs += $DbName
        
        # Backup completo database
        $DatabaseSql = "$BackupPath\database.sql"
        Write-Host "[INFO] Creazione backup completo database..." -ForegroundColor Blue
        
        $Process = Start-Process -FilePath $MysqldumpPath -ArgumentList $MysqldumpArgs -RedirectStandardOutput $DatabaseSql -RedirectStandardError "$BackupPath\backup_error.log" -Wait -PassThru
        
        if ($Process.ExitCode -eq 0) {
            Write-Host "[OK] Database salvato" -ForegroundColor Green
            
            # Verifica dimensione file
            $FileInfo = Get-Item $DatabaseSql
            Write-Host "[OK] File database.sql creato ($($FileInfo.Length) bytes)" -ForegroundColor Green
            
            # Backup solo struttura
            $StructureArgs = $MysqldumpArgs + @("--no-data")
            $StructureSql = "$BackupPath\database_structure.sql"
            $Process2 = Start-Process -FilePath $MysqldumpPath -ArgumentList $StructureArgs -RedirectStandardOutput $StructureSql -RedirectStandardError "$BackupPath\structure_error.log" -Wait -PassThru
            
            # Backup solo dati
            $DataArgs = $MysqldumpArgs + @("--no-create-info")
            $DataSql = "$BackupPath\database_data.sql"
            $Process3 = Start-Process -FilePath $MysqldumpPath -ArgumentList $DataArgs -RedirectStandardOutput $DataSql -RedirectStandardError "$BackupPath\data_error.log" -Wait -PassThru
            
            Write-Host "[OK] Backup database completo" -ForegroundColor Green
        } else {
            Write-Host "[WARNING] Problemi backup database (Exit Code: $($Process.ExitCode))" -ForegroundColor Yellow
            
            # Mostra errori se esistono
            $ErrorLog = "$BackupPath\backup_error.log"
            if (Test-Path $ErrorLog) {
                $ErrorContent = Get-Content $ErrorLog -Raw
                if (![string]::IsNullOrWhiteSpace($ErrorContent)) {
                    Write-Host "[ERROR] Dettagli errore:" -ForegroundColor Red
                    Write-Host $ErrorContent -ForegroundColor Red
                }
            }
        }
    } catch {
        Write-Host "[WARNING] Errore durante backup database: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Crea file info backup
$InfoFile = "$BackupPath\backup_info.txt"
$InfoContent = @"
Backup Gestione Scontrini PHP - Windows
=========================================

Data backup: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Sistema: Windows PowerShell

Database:
- Host: $DbHost
- Nome: $DbName  
- User: $DbUser

File inclusi:
- files.zip: Tutti i file dell'applicazione
- database.sql: Backup completo database
- database_structure.sql: Solo struttura database
- database_data.sql: Solo dati database
"@

Set-Content -Path $InfoFile -Value $InfoContent -Encoding UTF8
Write-Host "[OK] File info creato" -ForegroundColor Green

# Comprimi tutto in un unico archivio
Write-Host "[INFO] Compressione finale..." -ForegroundColor Blue

try {
    $FinalZip = "$BackupDir\$BackupName.zip"
    Compress-Archive -Path "$BackupPath\*" -DestinationPath $FinalZip -Force -CompressionLevel Optimal
    
    # Rimuovi cartella temporanea
    Remove-Item -Path $BackupPath -Recurse -Force
    
    Write-Host "[OK] Backup completato: $FinalZip" -ForegroundColor Green
    
    # Mostra dimensione
    $FinalFileInfo = Get-Item $FinalZip
    Write-Host "[INFO] Dimensione backup: $($FinalFileInfo.Length) bytes" -ForegroundColor Blue
} catch {
    Write-Host "[WARNING] Problemi compressione finale: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Pulizia backup vecchi (opzionale)
Write-Host ""
$Cleanup = Read-Host "Vuoi eliminare i backup più vecchi di 30 giorni? (s/N)"
if ($Cleanup -eq "s" -or $Cleanup -eq "S") {
    Write-Host "[INFO] Eliminazione backup vecchi..." -ForegroundColor Blue
    
    try {
        $CutoffDate = (Get-Date).AddDays(-30)
        Get-ChildItem -Path $BackupDir -Filter "scontrini_backup_*.zip" | Where-Object { $_.CreationTime -lt $CutoffDate } | Remove-Item -Force
        
        Write-Host "[OK] Backup vecchi eliminati" -ForegroundColor Green
    } catch {
        Write-Host "[WARNING] Errore eliminazione backup vecchi: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "===========================" -ForegroundColor Green
Write-Host "   Backup completato!" -ForegroundColor Green  
Write-Host "===========================" -ForegroundColor Green
Write-Host ""
Write-Host "Percorso: $BackupDir\$BackupName.zip"
Write-Host "Per ripristinare: usa restore.ps1 o estrai manualmente"
Write-Host ""

Read-Host "Premi Enter per continuare"