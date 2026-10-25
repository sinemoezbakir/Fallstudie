@echo off
rem ---------------------------------------------------------------------------
rem  Maliá Budget starten (Doppelklick genügt)
rem   1. startet MariaDB aus XAMPP, falls sie noch nicht läuft
rem   2. legt beim ersten Mal die Datenbank malia_budget mit Demo-Daten an
rem   3. startet die App auf http://localhost:8000 und öffnet den Browser
rem  Anderer XAMPP-Ordner:  set XAMPP=D:\xampp  vor dem Aufruf
rem  Anderer Port:          start-demo.bat 8080
rem ---------------------------------------------------------------------------
chcp 65001 >nul
cd /d "%~dp0"
if "%XAMPP%"=="" set XAMPP=C:\xampp
set PORT=%1
if "%PORT%"=="" set PORT=8000
set WIN=%SystemRoot%\System32

if not exist "%XAMPP%\php\php.exe" (
    echo XAMPP nicht gefunden unter %XAMPP% - bitte XAMPP installieren oder XAMPP=... setzen.
    pause
    exit /b 1
)

"%WIN%\tasklist.exe" /FI "IMAGENAME eq mysqld.exe" | "%WIN%\find.exe" /I "mysqld.exe" >nul
if errorlevel 1 (
    echo Starte MariaDB ...
    start "MariaDB (Maliá Budget)" /MIN "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
    "%WIN%\timeout.exe" /t 4 /nobreak >nul
)

"%XAMPP%\mysql\bin\mysql.exe" -uroot -e "USE malia_budget" 2>nul
if errorlevel 1 (
    echo Lege Datenbank malia_budget mit Demo-Daten an ...
    "%XAMPP%\mysql\bin\mysql.exe" -uroot --default-character-set=utf8mb4 < database\schema.sql
    "%XAMPP%\mysql\bin\mysql.exe" -uroot --default-character-set=utf8mb4 malia_budget < database\demodaten.sql
)

echo.
echo  Maliá Budget läuft auf http://localhost:%PORT%
echo  Demo-Login: finance / demo1234 (danach oben die Ansicht wechseln)
echo  Beenden: dieses Fenster schließen oder Strg+C
echo.
start "" http://localhost:%PORT%
"%XAMPP%\php\php.exe" -S localhost:%PORT% -t public
