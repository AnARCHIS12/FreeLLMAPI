#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is required for production mode."
    echo "Install Docker first, then run this file again."
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose is required."
    echo "Install Docker Compose first, then run this file again."
    exit 1
fi

if [ ! -f .env.prod ]; then
    cp .env.prod.example .env.prod
    KEY="$(openssl rand -hex 32 2>/dev/null || date +%s | sha256sum | awk '{print $1}')"
    sed -i "s/change_me_to_64_hex_characters/$KEY/" .env.prod
    echo
    echo ".env.prod was created."
    echo "Edit DOMAIN=example.com with your real domain, then run ./start-prod.sh again."
    echo
    exit 0
fi

DOMAIN_VALUE="$(grep '^DOMAIN=' .env.prod | cut -d= -f2-)"
if [ -z "$DOMAIN_VALUE" ] || [ "$DOMAIN_VALUE" = "example.com" ]; then
    echo "Edit .env.prod and set DOMAIN to your real domain first."
    exit 1
fi

echo "Starting production deployment for: $DOMAIN_VALUE"
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
echo
echo "FreeLLMAPI is live at:"
echo "https://$DOMAIN_VALUE"
