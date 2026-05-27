#!/bin/sh
set -eu

cd "$(dirname "$0")"
APP_URL="http://localhost:3001"

echo
echo "FreeLLMAPI local launcher"
echo "========================="
echo

if ! command -v php >/dev/null 2>&1; then
    echo "PHP was not found."
    if command -v brew >/dev/null 2>&1; then
        echo "Installing PHP with Homebrew..."
        brew install php
    else
        echo "Homebrew was not found."
        echo "Installing Homebrew first, then PHP..."
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        if [ -x /opt/homebrew/bin/brew ]; then
            eval "$(/opt/homebrew/bin/brew shellenv)"
        elif [ -x /usr/local/bin/brew ]; then
            eval "$(/usr/local/bin/brew shellenv)"
        fi
        brew install php
    fi
fi

mkdir -p data

echo "Starting FreeLLMAPI..."
echo "Open: $APP_URL"
echo

open "$APP_URL" >/dev/null 2>&1 &
php -S localhost:3001 -t .
