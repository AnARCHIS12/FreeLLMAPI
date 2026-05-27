@echo off
setlocal

cd /d "%~dp0"

echo.
echo FreeLLMAPI production launcher
echo ==============================
echo.

where docker >nul 2>nul
if %errorlevel% neq 0 (
    echo Docker was not found.
    echo Install Docker Desktop first:
    echo https://www.docker.com/products/docker-desktop/
    echo.
    pause
    exit /b 1
)

docker compose version >nul 2>nul
if %errorlevel% neq 0 (
    echo Docker Compose was not found.
    echo Open Docker Desktop, wait until it is running, then try again.
    echo.
    pause
    exit /b 1
)

if not exist .env.prod (
    copy .env.prod.example .env.prod >nul
    powershell -NoProfile -ExecutionPolicy Bypass -Command "$key=-join ((1..64)|ForEach-Object {'{0:x}' -f (Get-Random -Maximum 16)}); (Get-Content '.env.prod') -replace 'change_me_to_64_hex_characters', $key | Set-Content '.env.prod'"
    echo .env.prod was created.
    echo.
    echo Edit .env.prod and replace:
    echo DOMAIN=example.com
    echo with your real domain.
    echo.
    pause
    exit /b 0
)

for /f "tokens=1,* delims==" %%A in (.env.prod) do (
    if "%%A"=="DOMAIN" set "DOMAIN_VALUE=%%B"
)

if "%DOMAIN_VALUE%"=="" (
    echo Edit .env.prod and set DOMAIN to your real domain first.
    pause
    exit /b 1
)

if "%DOMAIN_VALUE%"=="example.com" (
    echo Edit .env.prod and replace example.com with your real domain first.
    pause
    exit /b 1
)

echo Starting production deployment for: %DOMAIN_VALUE%
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build

echo.
echo FreeLLMAPI is live at:
echo https://%DOMAIN_VALUE%
echo.
pause
