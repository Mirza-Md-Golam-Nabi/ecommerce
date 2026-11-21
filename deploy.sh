#!/bin/bash
set -e

PHP="/opt/cpanel/ea-php83/root/usr/bin/php"

echo "🚀 Deployment started..."

# Pull the latest code from Git
git pull origin dev
echo "✅ Git pull done"

# Download composer.phar if it isn't here yet
if [ ! -f composer.phar ]; then
    curl -sS https://getcomposer.org/installer | $PHP
    echo "✅ Composer downloaded"
fi

# Run composer install before artisan down — if the new code doesn't
# match the old vendor/ folder, artisan down itself will crash, and
# the next deploy attempt will get stuck at this same spot (deadlock).
$PHP composer.phar install --no-dev --optimize-autoloader
echo "✅ Composer done"

# Write KEY="value" into .env (replaces the existing line, or appends it)
set_env() {
    local key="$1"
    local value="$2"
    value="${value//\\/\\\\}"
    value="${value//\"/\\\"}"
    local line="${key}=\"${value}\""

    if grep -q "^${key}=" .env; then
        local escaped="${line//\\/\\\\}"
        escaped="${escaped//&/\\&}"
        escaped="${escaped//|/\\|}"
        sed -i "s|^${key}=.*|${escaped}|" .env
    else
        echo "$line" >> .env
    fi
}

# Create .env on the first deploy and fill in the database settings
if [ ! -f .env ]; then
    cp .env.example .env

    DEFAULT_APP_URL="https://$(basename "$PWD")"
    read -r -p "APP_URL [$DEFAULT_APP_URL]: " APP_URL
    APP_URL="${APP_URL:-$DEFAULT_APP_URL}"
    read -r -p "DB_DATABASE: " DB_DATABASE
    read -r -s -p "DB_PASSWORD: " DB_PASSWORD
    echo

    set_env APP_ENV "production"
    set_env APP_DEBUG "false"
    set_env APP_URL "$APP_URL"
    set_env DB_DATABASE "$DB_DATABASE"
    set_env DB_USERNAME "nextfusi_mirza"
    set_env DB_PASSWORD "$DB_PASSWORD"

    $PHP artisan key:generate --force
    echo "✅ .env created"
fi

# Send every request to public/ when the domain points at the project root
if [ ! -f .htaccess ]; then
    cat > .htaccess <<'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L,QSA]
</IfModule>
EOF
    echo "✅ .htaccess created"
fi

$PHP artisan down
echo "✅ Maintenance mode ON"

# Migrate
$PHP artisan migrate --force
echo "✅ Migration done"

# Cache clear
$PHP artisan optimize:clear
$PHP artisan filament:optimize-clear
echo "✅ Cache cleared"

# Cache rebuild
$PHP artisan optimize
$PHP artisan filament:optimize
echo "✅ Cache rebuilt"

# Force the queue worker to load the new code
$PHP artisan queue:restart
echo "✅ Queue restarted"

$PHP artisan up
echo "✅ Maintenance mode OFF"

echo "🎉 Deployment finished!"
