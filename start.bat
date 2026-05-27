@echo off
setlocal

cd /d "%~dp0"
set "APP_URL=http://localhost:3001"

echo.
echo FreeLLMAPI local launcher
echo =========================
echo.

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo PHP was not found.
    echo.
    where winget >nul 2>nul
    if %errorlevel% equ 0 (
        echo Installing PHP with winget...
        winget install --id PHP.PHP -e --accept-package-agreements --accept-source-agreements
    ) else (
        echo winget was not found.
        echo Please install PHP manually from:
        echo https://windows.php.net/download/
        echo.
        pause
        exit /b 1
    )
)

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo PHP still cannot be found.
    echo Close this window, reopen it, then double-click start.bat again.
    echo.
    pause
    exit /b 1
)

if not exist data mkdir data

echo Starting FreeLLMAPI...
echo Open: %APP_URL%
echo.

start "" "%APP_URL%"
php -S localhost:3001 -t .

pause
