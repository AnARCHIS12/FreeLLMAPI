#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"
APP_URL="http://localhost:3001"

need_php_extensions() {
    php -m | grep -qi '^sqlite3$' || return 1
    php -m | grep -qi '^curl$' || return 1
    php -m | grep -qi '^openssl$' || return 1
}

install_php_linux() {
    if command -v apt-get >/dev/null 2>&1; then
        sudo apt-get update
        sudo apt-get install -y php-cli php-sqlite3 php-curl php-mbstring
        return
    fi

    if command -v dnf >/dev/null 2>&1; then
        sudo dnf install -y php-cli php-sqlite3 php-curl php-mbstring
        return
    fi

    if command -v pacman >/dev/null 2>&1; then
        sudo pacman -Sy --needed php php-sqlite
        return
    fi

    if command -v apk >/dev/null 2>&1; then
        sudo apk add php php-sqlite3 php-curl php-openssl
        return
    fi

    echo "Could not detect your Linux package manager."
    echo "Install PHP with SQLite3, cURL and OpenSSL, then run this file again."
    exit 1
}

echo
echo "FreeLLMAPI local launcher"
echo "========================="
echo

if ! command -v php >/dev/null 2>&1; then
    echo "PHP was not found. Installing PHP..."
    install_php_linux
fi

if ! need_php_extensions; then
    echo "Some PHP extensions are missing. Installing common PHP packages..."
    install_php_linux
fi

mkdir -p data

echo "Starting FreeLLMAPI..."
echo "Open: $APP_URL"
echo

if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$APP_URL" >/dev/null 2>&1 &
fi

php -S localhost:3001 -t .
