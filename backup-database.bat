@echo off
REM ========================================
REM Database Backup Script - Qxpress Laundry
REM ========================================

echo.
echo ========================================
echo  Database Backup - Qxpress Laundry
echo ========================================
echo.

REM Set variables
set TIMESTAMP=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set DB_NAME=tugasakhir
set DB_USER=root
set DB_PASS=
set BACKUP_DIR=backup
set BACKUP_FILE=%BACKUP_DIR%\laundry_backup_%TIMESTAMP%.sql

REM Create backup directory if not exists
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo [%time%] Starting database backup...
echo Database: %DB_NAME%
echo Backup file: %BACKUP_FILE%
echo.

REM Execute mysqldump
REM Note: Adjust path to mysqldump if using XAMPP/Laragon
REM XAMPP: "C:\xampp\mysql\bin\mysqldump"
REM Laragon: "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump"

REM Using system PATH (if MySQL in PATH)
mysqldump -u %DB_USER% %DB_NAME% > "%BACKUP_FILE%" 2>&1

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [%time%] SUCCESS! Backup completed.
    echo File saved: %BACKUP_FILE%
    echo.
    
    REM Get file size
    for %%A in ("%BACKUP_FILE%") do set SIZE=%%~zA
    echo File size: %SIZE% bytes
    echo.
) else (
    echo.
    echo [%time%] ERROR! Backup failed.
    echo Please check:
    echo  - MySQL service is running
    echo  - Database name is correct: %DB_NAME%
    echo  - mysqldump is in system PATH
    echo.
)

REM Clean up old backups (keep last 7 days)
echo Cleaning old backups (keeping last 7 days)...
forfiles /P "%BACKUP_DIR%" /M *.sql /D -7 /C "cmd /c del @file" 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Old backups deleted.
) else (
    echo No old backups to delete.
)

echo.
echo ========================================
echo  Backup process completed!
echo ========================================
echo.
pause
