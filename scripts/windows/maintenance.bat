@echo off
echo.
echo ===========================================
echo   Manutenzione Gestione Scontrini - Windows  
echo ===========================================
echo.

REM Colori
set "GREEN=[92m"
set "YELLOW=[93m"
set "RED=[91m"  
set "BLUE=[94m"
set "NC=[0m"

set "SCRIPT_DIR=%~dp0"

echo %BLUE%[INFO]%NC% Avvio controlli sistema...

REM Verifica PHP
php -v >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%[OK]%NC% PHP trovato
    for /f "tokens=2" %%a in ('php -v ^| findstr /C:"PHP"') do (
        echo %BLUE%[INFO]%NC% Versione PHP: %%a
        goto :php_found
    )
    :php_found
) else (
    echo %RED%[ERROR]%NC% PHP non trovato nel PATH
    goto :end
)

REM Verifica estensioni PHP
echo %BLUE%[INFO]%NC% Controllo estensioni PHP...

php -m | findstr /C:"pdo" >nul && echo %GREEN%[OK]%NC% Estensione PDO: OK || echo %YELLOW%[WARNING]%NC% PDO mancante
php -m | findstr /C:"pdo_mysql" >nul && echo %GREEN%[OK]%NC% Estensione PDO_MySQL: OK || echo %YELLOW%[WARNING]%NC% PDO_MySQL mancante
php -m | findstr /C:"mysqli" >nul && echo %GREEN%[OK]%NC% Estensione MySQLi: OK || echo %YELLOW%[WARNING]%NC% MySQLi mancante
php -m | findstr /C:"json" >nul && echo %GREEN%[OK]%NC% Estensione JSON: OK || echo %YELLOW%[WARNING]%NC% JSON mancante
php -m | findstr /C:"mbstring" >nul && echo %GREEN%[OK]%NC% Estensione MBString: OK || echo %YELLOW%[WARNING]%NC% MBString mancante

REM Spazio disco
echo %BLUE%[INFO]%NC% Controllo spazio disco...
for /f "tokens=3" %%a in ('dir /-c "%SCRIPT_DIR%..\..\" ^| findstr /C:"byte"') do (
    echo %BLUE%[INFO]%NC% Spazio disponibile nel drive
)

REM Verifica file principali
echo %BLUE%[INFO]%NC% Verifica file principali...

if exist "%SCRIPT_DIR%..\..\config.php" (
    echo %GREEN%[OK]%NC% config.php: OK
) else (
    echo %RED%[ERROR]%NC% config.php: MANCANTE
)

if exist "%SCRIPT_DIR%..\..\index.php" (
    echo %GREEN%[OK]%NC% index.php: OK  
) else (
    echo %RED%[ERROR]%NC% index.php: MANCANTE
)

if exist "%SCRIPT_DIR%..\..\includes\database.php" (
    echo %GREEN%[OK]%NC% database.php: OK
) else (
    echo %RED%[ERROR]%NC% database.php: MANCANTE
)

REM Test sintassi PHP
echo %BLUE%[INFO]%NC% Test sintassi file PHP...

if exist "%SCRIPT_DIR%..\..\config.php" (
    php -l "%SCRIPT_DIR%..\..\config.php" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo %GREEN%[OK]%NC% Sintassi config.php: OK
    ) else (
        echo %YELLOW%[WARNING]%NC% Problemi sintassi config.php
    )
)

if exist "%SCRIPT_DIR%..\..\index.php" (
    php -l "%SCRIPT_DIR%..\..\index.php" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo %GREEN%[OK]%NC% Sintassi index.php: OK
    ) else (
        echo %YELLOW%[WARNING]%NC% Problemi sintassi index.php
    )
)

REM Test database se config.php esiste
if exist "%SCRIPT_DIR%..\..\config.php" (
    echo %BLUE%[INFO]%NC% Test connessione database...
    
    REM Estrai parametri database
    for /f "tokens=4 delims='" %%a in ('findstr "DB_HOST" "%SCRIPT_DIR%..\..\config.php"') do set "DB_HOST=%%a"
    for /f "tokens=4 delims='" %%a in ('findstr "DB_NAME" "%SCRIPT_DIR%..\..\config.php"') do set "DB_NAME=%%a"
    for /f "tokens=4 delims='" %%a in ('findstr "DB_USER" "%SCRIPT_DIR%..\..\config.php"') do set "DB_USER=%%a"
    for /f "tokens=4 delims='" %%a in ('findstr "DB_PASS" "%SCRIPT_DIR%..\..\config.php"') do set "DB_PASS=%%a"
    
    REM Cerca mysql client
    set "MYSQL_PATH="
    if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe"
    if exist "%PROGRAMFILES%\MySQL\MySQL Server 8.0\bin\mysql.exe" set "MYSQL_PATH=%PROGRAMFILES%\MySQL\MySQL Server 8.0\bin\mysql.exe"
    
    if defined MYSQL_PATH (
        "%MYSQL_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% -e "SELECT 1;" "%DB_NAME%" >nul 2>&1
        if %ERRORLEVEL% EQU 0 (
            echo %GREEN%[OK]%NC% Connessione database: OK
            
            REM Statistiche database
            for /f %%a in ('"%MYSQL_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% "%DB_NAME%" -se "SELECT COUNT(*) FROM scontrini;" 2^>nul') do (
                echo %BLUE%[INFO]%NC% Scontrini nel database: %%a
            )
            
            for /f %%a in ('"%MYSQL_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% "%DB_NAME%" -se "SELECT COUNT(*) FROM utenti;" 2^>nul') do (
                echo %BLUE%[INFO]%NC% Utenti nel database: %%a
            )
            
        ) else (
            echo %RED%[ERROR]%NC% Impossibile connettersi al database
        )
    ) else (
        echo %YELLOW%[WARNING]%NC% MySQL client non trovato
    )
)

REM Menu operazioni
echo.
echo %BLUE%[MENU]%NC% Operazioni disponibili:
echo 1. Backup automatico
echo 2. Pulizia file temporanei
echo 3. Test completo sistema
echo 4. Genera report
echo 5. Esci
echo.

set /p "choice=Scegli operazione (1-5): "

if "%choice%"=="1" (
    echo %BLUE%[INFO]%NC% Avvio backup...
    if exist "%SCRIPT_DIR%backup.bat" (
        call "%SCRIPT_DIR%backup.bat"
    ) else (
        echo %RED%[ERROR]%NC% backup.bat non trovato
    )
    goto :menu_end
)

if "%choice%"=="2" (
    echo %BLUE%[INFO]%NC% Pulizia file temporanei...
    
    del /q "%SCRIPT_DIR%*.tmp" 2>nul
    del /q "%SCRIPT_DIR%*.bak" 2>nul  
    del /q "%SCRIPT_DIR%*~" 2>nul
    
    echo %GREEN%[OK]%NC% Pulizia completata
    goto :menu_end
)

if "%choice%"=="3" (
    echo %BLUE%[INFO]%NC% Test completo in corso...
    
    if exist "%SCRIPT_DIR%test.php" (
        php "%SCRIPT_DIR%test.php"
    ) else (
        echo %YELLOW%[WARNING]%NC% test.php non trovato
    )
    goto :menu_end
)

if "%choice%"=="4" (
    echo %BLUE%[INFO]%NC% Generazione report sistema...
    
    set "REPORT_FILE=%SCRIPT_DIR%system_report_windows.txt"
    
    echo Report Sistema - Gestione Scontrini PHP Windows > "%REPORT_FILE%"
    echo =============================================== >> "%REPORT_FILE%"
    echo Generato: %date% %time% >> "%REPORT_FILE%"
    echo. >> "%REPORT_FILE%"
    
    echo SISTEMA: >> "%REPORT_FILE%"
    echo - OS: Windows >> "%REPORT_FILE%"
    systeminfo | findstr /C:"OS Name" /C:"OS Version" >> "%REPORT_FILE%"
    php -v | findstr /C:"PHP" >> "%REPORT_FILE%"
    echo. >> "%REPORT_FILE%"
    
    if defined DB_HOST (
        echo DATABASE: >> "%REPORT_FILE%"
        echo - Host: %DB_HOST% >> "%REPORT_FILE%"
        echo - Database: %DB_NAME% >> "%REPORT_FILE%"
        echo - User: %DB_USER% >> "%REPORT_FILE%"
        echo. >> "%REPORT_FILE%"
    )
    
    echo SPAZIO DISCO: >> "%REPORT_FILE%"
    dir /-c "%SCRIPT_DIR%" | findstr /C:"byte" >> "%REPORT_FILE%"
    
    echo %GREEN%[OK]%NC% Report salvato in: %REPORT_FILE%
    goto :menu_end
)

if "%choice%"=="5" (
    goto :end
)

echo %RED%[ERROR]%NC% Scelta non valida

:menu_end
echo.
pause
goto :end

:end
echo.
echo %GREEN%[SUCCESS]%NC% Operazioni completate
echo.
pause