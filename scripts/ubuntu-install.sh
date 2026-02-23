#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
DEFAULT_SOURCE_APP_DIR="${REPO_ROOT}/app"

log() {
    echo -e "\033[1;34m[INFO]\033[0m $*"
}

warn() {
    echo -e "\033[1;33m[WARN]\033[0m $*"
}

error() {
    echo -e "\033[1;31m[ERROR]\033[0m $*" >&2
}

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        error "Required command not found: $1"
        exit 1
    fi
}

ask() {
    local prompt="$1"
    local default_value="${2:-}"
    local result=""

    if [[ -n "${default_value}" ]]; then
        read -r -p "${prompt} [${default_value}]: " result
        echo "${result:-$default_value}"
    else
        read -r -p "${prompt}: " result
        echo "${result}"
    fi
}

ask_secret() {
    local prompt="$1"
    local result=""
    read -r -s -p "${prompt}: " result
    echo
    echo "${result}"
}

normalize_domain_input() {
    local value="$1"
    value="$(printf '%s' "${value}" | tr '[:upper:]' '[:lower:]')"
    value="${value#http://}"
    value="${value#https://}"
    value="${value%%/*}"
    value="${value%.}"
    printf '%s' "${value}"
}

confirm() {
    local prompt="$1"
    local answer=""
    read -r -p "${prompt} [y/N]: " answer
    [[ "${answer,,}" =~ ^(y|yes)$ ]]
}

ensure_not_empty() {
    local value="$1"
    local field="$2"
    if [[ -z "${value}" ]]; then
        error "${field} cannot be empty."
        exit 1
    fi
}

ensure_matches() {
    local value="$1"
    local regex="$2"
    local field="$3"

    if [[ ! "${value}" =~ ${regex} ]]; then
        error "Invalid ${field}: ${value}"
        exit 1
    fi
}

sql_escape() {
    printf '%s' "$1" | sed "s/'/''/g"
}

upsert_env() {
    local file="$1"
    local key="$2"
    local value="$3"
    local tmp_file

    touch "${file}"
    tmp_file="$(mktemp)"

    awk -v env_key="${key}" -v env_value="${value}" '
        BEGIN { updated = 0 }
        $0 ~ ("^" env_key "=") {
            print env_key "=" env_value
            updated = 1
            next
        }
        { print }
        END {
            if (updated == 0) {
                print env_key "=" env_value
            }
        }
    ' "${file}" > "${tmp_file}"

    mv "${tmp_file}" "${file}"
}

run_as_app_user() {
    if [[ "$(id -un)" == "${APP_USER}" ]]; then
        "$@"
    else
        sudo -u "${APP_USER}" "$@"
    fi
}

if [[ "${EUID}" -eq 0 ]]; then
    error "Please run this script as a regular user with sudo privileges (not root)."
    exit 1
fi

require_command sudo
require_command bash
require_command grep
require_command awk

if [[ ! -f "${DEFAULT_SOURCE_APP_DIR}/artisan" ]]; then
    warn "Default source app directory not found at ${DEFAULT_SOURCE_APP_DIR}."
    warn "You can provide a custom path in prompts."
fi

if [[ -f /etc/os-release ]]; then
    # shellcheck disable=SC1091
    source /etc/os-release
    if [[ "${ID:-}" != "ubuntu" ]]; then
        warn "Detected OS: ${PRETTY_NAME:-unknown}. This script is optimized for Ubuntu 22.04/24.04."
        confirm "Continue anyway?" || exit 1
    fi
fi

log "KKTC ERP SaaS Ubuntu setup is starting."

DOMAIN="$(ask "Primary domain (example: domain.com)")"
ensure_not_empty "${DOMAIN}" "Primary domain"
DOMAIN="$(normalize_domain_input "${DOMAIN}")"
ensure_matches "${DOMAIN}" '^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$' "primary domain"

CENTRAL_SUBDOMAIN="$(ask "Central panel subdomain" "admin")"
CENTRAL_SUBDOMAIN="$(printf '%s' "${CENTRAL_SUBDOMAIN}" | tr '[:upper:]' '[:lower:]')"
ensure_matches "${CENTRAL_SUBDOMAIN}" '^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$' "central subdomain"
CENTRAL_DOMAIN="${CENTRAL_SUBDOMAIN}.${DOMAIN}"
CENTRAL_DOMAINS="$(ask "Central domains CSV" "127.0.0.1,localhost,${CENTRAL_DOMAIN}")"

APP_PATH="$(ask "Deploy path for Laravel app" "/var/www/kktc-erp-saas")"
APP_USER="$(ask "System user owning project files" "${USER}")"
SOURCE_APP_DIR="$(ask "Source Laravel app directory" "${DEFAULT_SOURCE_APP_DIR}")"
ensure_not_empty "${SOURCE_APP_DIR}" "Source Laravel app directory"

if ! id "${APP_USER}" >/dev/null 2>&1; then
    error "System user does not exist: ${APP_USER}"
    exit 1
fi

if [[ ! -f "${SOURCE_APP_DIR}/artisan" ]]; then
    error "Invalid source app directory: ${SOURCE_APP_DIR} (artisan file missing)."
    exit 1
fi
ensure_matches "${APP_PATH}" '^/' "deploy path"

TIMEZONE="$(ask "Server timezone" "Europe/Istanbul")"
PHP_VERSION="$(ask "PHP version (8.2/8.3)" "8.3")"
ensure_matches "${PHP_VERSION}" '^[0-9]+\.[0-9]+$' "PHP version"
NODE_MAJOR="$(ask "Node.js major version" "20")"
ensure_matches "${NODE_MAJOR}" '^[0-9]+$' "Node.js major version"

DB_CENTRAL_DATABASE="$(ask "Central database name" "erp_central")"
ensure_matches "${DB_CENTRAL_DATABASE}" '^[A-Za-z0-9_]+$' "central database name"
DB_APP_USER="$(ask "Database app user" "erp_app")"
ensure_matches "${DB_APP_USER}" '^[A-Za-z0-9_]+$' "database app user"
DB_APP_PASSWORD="$(ask_secret "Database app password")"
ensure_not_empty "${DB_APP_PASSWORD}" "Database app password"
DB_HOST="$(ask "Database host" "127.0.0.1")"
DB_PORT="$(ask "Database port" "3306")"
ensure_matches "${DB_PORT}" '^[0-9]+$' "database port"
TENANT_DB_PREFIX="$(ask "Tenant database prefix" "tenant_")"
ensure_matches "${TENANT_DB_PREFIX}" '^[A-Za-z0-9_]+$' "tenant database prefix"

RUN_SEED="no"
ADMIN_EMAIL=""
ADMIN_PASSWORD=""
if confirm "Run central db seed (creates super-admin user)?"; then
    RUN_SEED="yes"
    ADMIN_EMAIL="$(ask "Initial super-admin email" "admin@${DOMAIN}")"
    ensure_matches "${ADMIN_EMAIL}" '^[^@[:space:]]+@[^@[:space:]]+\.[^@[:space:]]+$' "initial super-admin email"
    ADMIN_PASSWORD="$(ask_secret "Initial super-admin password (used after seeding)")"
    ensure_not_empty "${ADMIN_PASSWORD}" "Initial super-admin password"
fi

SSL_MODE="$(ask "SSL mode (wildcard-cloudflare / central-only)" "wildcard-cloudflare")"
SSL_MODE="${SSL_MODE,,}"
SSL_EMAIL="$(ask "SSL contact email" "admin@${DOMAIN}")"
ensure_not_empty "${SSL_EMAIL}" "SSL contact email"

CLOUDFLARE_API_TOKEN=""
if [[ "${SSL_MODE}" == "wildcard-cloudflare" ]]; then
    CLOUDFLARE_API_TOKEN="$(ask_secret "Cloudflare API token (required for wildcard SSL)")"
    ensure_not_empty "${CLOUDFLARE_API_TOKEN}" "Cloudflare API token"
elif [[ "${SSL_MODE}" != "central-only" ]]; then
    error "Invalid SSL mode: ${SSL_MODE}. Allowed values: wildcard-cloudflare or central-only."
    exit 1
fi

log "Configuration summary"
echo "  Domain:                  ${DOMAIN}"
echo "  Central domain:          ${CENTRAL_DOMAIN}"
echo "  Central domains:         ${CENTRAL_DOMAINS}"
echo "  App path:                ${APP_PATH}"
echo "  App owner user:          ${APP_USER}"
echo "  Source app path:         ${SOURCE_APP_DIR}"
echo "  PHP version:             ${PHP_VERSION}"
echo "  Node major:              ${NODE_MAJOR}"
echo "  DB host/port:            ${DB_HOST}:${DB_PORT}"
echo "  Central DB:              ${DB_CENTRAL_DATABASE}"
echo "  DB app user:             ${DB_APP_USER}"
echo "  Tenant DB prefix:        ${TENANT_DB_PREFIX}"
echo "  Run seed:                ${RUN_SEED}"
if [[ "${RUN_SEED}" == "yes" ]]; then
echo "  Seed admin email:        ${ADMIN_EMAIL}"
fi
echo "  SSL mode:                ${SSL_MODE}"

confirm "Proceed with installation?" || exit 1

log "Installing system dependencies..."
sudo apt update
sudo apt install -y software-properties-common ca-certificates curl gnupg lsb-release unzip git rsync ufw
sudo apt install -y nginx mariadb-server supervisor
sudo apt install -y certbot python3-certbot-nginx

sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    "php${PHP_VERSION}" \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-sqlite3" \
    composer

curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | sudo -E bash -
sudo apt install -y nodejs

sudo timedatectl set-timezone "${TIMEZONE}" || warn "Failed to set timezone; continuing."

log "Enabling services..."
sudo systemctl enable --now nginx
sudo systemctl enable --now mariadb
sudo systemctl enable --now "php${PHP_VERSION}-fpm"
sudo systemctl enable --now supervisor

log "Configuring firewall..."
sudo ufw allow OpenSSH
sudo ufw allow "Nginx Full"
sudo ufw --force enable

log "Deploying application code..."
sudo mkdir -p "${APP_PATH}"
# Avoid destructive sync to keep existing .env/storage data on reruns.
sudo rsync -a "${SOURCE_APP_DIR}/" "${APP_PATH}/"
sudo chown -R "${APP_USER}:www-data" "${APP_PATH}"

cd "${APP_PATH}"

log "Installing PHP/Node dependencies..."
run_as_app_user composer install --no-interaction --prefer-dist --optimize-autoloader
run_as_app_user npm install --no-audit --no-fund
run_as_app_user npm run build

if [[ ! -f "${APP_PATH}/.env" ]]; then
    cp "${APP_PATH}/.env.example" "${APP_PATH}/.env"
fi

ENV_FILE="${APP_PATH}/.env"
upsert_env "${ENV_FILE}" "APP_NAME" "\"KKTC ERP SaaS\""
upsert_env "${ENV_FILE}" "APP_ENV" "production"
upsert_env "${ENV_FILE}" "APP_DEBUG" "false"
upsert_env "${ENV_FILE}" "APP_URL" "https://${CENTRAL_DOMAIN}"
upsert_env "${ENV_FILE}" "LOG_LEVEL" "info"
upsert_env "${ENV_FILE}" "APP_TIMEZONE" "${TIMEZONE}"

upsert_env "${ENV_FILE}" "DB_CONNECTION" "central"
upsert_env "${ENV_FILE}" "DB_CENTRAL_CONNECTION" "central"
upsert_env "${ENV_FILE}" "DB_CENTRAL_DRIVER" "mariadb"
upsert_env "${ENV_FILE}" "DB_CENTRAL_HOST" "${DB_HOST}"
upsert_env "${ENV_FILE}" "DB_CENTRAL_PORT" "${DB_PORT}"
upsert_env "${ENV_FILE}" "DB_CENTRAL_DATABASE" "${DB_CENTRAL_DATABASE}"
upsert_env "${ENV_FILE}" "DB_CENTRAL_USERNAME" "${DB_APP_USER}"
upsert_env "${ENV_FILE}" "DB_CENTRAL_PASSWORD" "${DB_APP_PASSWORD}"
upsert_env "${ENV_FILE}" "DB_CENTRAL_STRICT_MODE" "true"

upsert_env "${ENV_FILE}" "DB_HOST" "${DB_HOST}"
upsert_env "${ENV_FILE}" "DB_PORT" "${DB_PORT}"
upsert_env "${ENV_FILE}" "DB_DATABASE" "${DB_CENTRAL_DATABASE}"
upsert_env "${ENV_FILE}" "DB_USERNAME" "${DB_APP_USER}"
upsert_env "${ENV_FILE}" "DB_PASSWORD" "${DB_APP_PASSWORD}"

upsert_env "${ENV_FILE}" "CENTRAL_DOMAINS" "${CENTRAL_DOMAINS}"
upsert_env "${ENV_FILE}" "TENANCY_DB_PREFIX" "${TENANT_DB_PREFIX}"
upsert_env "${ENV_FILE}" "TENANCY_DB_SUFFIX" ""
upsert_env "${ENV_FILE}" "DB_TENANT_DRIVER" "mariadb"
upsert_env "${ENV_FILE}" "DB_TENANT_HOST" "${DB_HOST}"
upsert_env "${ENV_FILE}" "DB_TENANT_PORT" "${DB_PORT}"
upsert_env "${ENV_FILE}" "DB_TENANT_USERNAME" "${DB_APP_USER}"
upsert_env "${ENV_FILE}" "DB_TENANT_PASSWORD" "${DB_APP_PASSWORD}"
upsert_env "${ENV_FILE}" "DB_TENANT_STRICT_MODE" "true"

upsert_env "${ENV_FILE}" "SESSION_DRIVER" "database"
upsert_env "${ENV_FILE}" "CACHE_STORE" "database"
upsert_env "${ENV_FILE}" "QUEUE_CONNECTION" "database"

log "Preparing MariaDB central database and user grants..."
DB_APP_PASSWORD_SQL="$(sql_escape "${DB_APP_PASSWORD}")"
DB_CENTRAL_DATABASE_SQL="${DB_CENTRAL_DATABASE}"

sudo mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_CENTRAL_DATABASE_SQL}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'localhost' IDENTIFIED BY '${DB_APP_PASSWORD_SQL}';
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_APP_PASSWORD_SQL}';
GRANT ALL PRIVILEGES ON \`${DB_CENTRAL_DATABASE_SQL}\`.* TO '${DB_APP_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_CENTRAL_DATABASE_SQL}\`.* TO '${DB_APP_USER}'@'127.0.0.1';
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON *.* TO '${DB_APP_USER}'@'localhost';
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON *.* TO '${DB_APP_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

log "Running Laravel setup commands..."
run_as_app_user php artisan key:generate --force
run_as_app_user php artisan config:clear
run_as_app_user php artisan cache:clear || true
run_as_app_user php artisan migrate --database=central --force

if [[ "${RUN_SEED}" == "yes" ]]; then
    run_as_app_user php artisan db:seed --database=central --force

    log "Updating seeded super-admin credentials..."
    APP_ADMIN_EMAIL="${ADMIN_EMAIL}" APP_ADMIN_PASSWORD="${ADMIN_PASSWORD}" run_as_app_user php <<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$adminEmail = getenv('APP_ADMIN_EMAIL') ?: 'admin@domain.com';
$adminPassword = getenv('APP_ADMIN_PASSWORD') ?: null;

if (!$adminPassword) {
    fwrite(STDERR, "Seeded admin password is empty.\n");
    exit(1);
}

$user = \App\Models\User::query()->where('email', 'admin@domain.com')->first();
if (!$user) {
    $user = \App\Models\User::query()->orderBy('id')->first();
}

if (!$user) {
    fwrite(STDERR, "No user found after seeding.\n");
    exit(1);
}

$user->email = strtolower($adminEmail);
$user->password = \Illuminate\Support\Facades\Hash::make($adminPassword);
$user->save();
PHP
fi

run_as_app_user php artisan storage:link || true
run_as_app_user php artisan optimize

sudo chown -R www-data:www-data "${APP_PATH}/storage" "${APP_PATH}/bootstrap/cache"
sudo chmod -R 775 "${APP_PATH}/storage" "${APP_PATH}/bootstrap/cache"

log "Writing Nginx configuration..."
sudo tee /etc/nginx/snippets/kktc-erp-laravel.conf >/dev/null <<EOF
root ${APP_PATH}/public;
index index.php index.html;

location / {
    try_files \$uri \$uri/ /index.php?\$query_string;
}

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
}

location ~ /\.(?!well-known).* {
    deny all;
}
EOF

SSL_ENABLED="no"
CERT_PATH_DOMAIN="${CENTRAL_DOMAIN}"
TENANT_SSL_ENABLED="no"

if [[ "${SSL_MODE}" == "wildcard-cloudflare" ]]; then
    log "Installing Certbot DNS plugin for Cloudflare..."
    sudo apt install -y python3-certbot-dns-cloudflare
    sudo tee /etc/letsencrypt/cloudflare.ini >/dev/null <<EOF
dns_cloudflare_api_token = ${CLOUDFLARE_API_TOKEN}
EOF
    sudo chmod 600 /etc/letsencrypt/cloudflare.ini

    log "Issuing wildcard certificate for ${DOMAIN} and *.${DOMAIN}..."
    sudo certbot certonly \
        --dns-cloudflare \
        --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
        -d "${DOMAIN}" \
        -d "*.${DOMAIN}" \
        --agree-tos \
        -m "${SSL_EMAIL}" \
        --non-interactive \
        --no-eff-email

    CERT_PATH_DOMAIN="${DOMAIN}"
    TENANT_SSL_ENABLED="yes"
else
    warn "Central-only SSL selected. Tenant wildcard will stay HTTP until wildcard cert is configured."
    log "Issuing certificate for ${CENTRAL_DOMAIN} via nginx challenge..."
    # Temporary HTTP config for challenge.
    sudo tee /etc/nginx/sites-available/kktc-erp.conf >/dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${CENTRAL_DOMAIN} *.${DOMAIN};
    include /etc/nginx/snippets/kktc-erp-laravel.conf;
}
EOF
    sudo ln -sf /etc/nginx/sites-available/kktc-erp.conf /etc/nginx/sites-enabled/kktc-erp.conf
    sudo rm -f /etc/nginx/sites-enabled/default
    sudo nginx -t
    sudo systemctl reload nginx

    sudo certbot --nginx \
        -d "${CENTRAL_DOMAIN}" \
        --agree-tos \
        -m "${SSL_EMAIL}" \
        --non-interactive \
        --redirect \
        --no-eff-email
fi

if [[ -f "/etc/letsencrypt/live/${CERT_PATH_DOMAIN}/fullchain.pem" ]]; then
    SSL_ENABLED="yes"
else
    error "SSL certificate was not created successfully."
    exit 1
fi

if [[ "${SSL_ENABLED}" == "yes" ]]; then
    if [[ "${TENANT_SSL_ENABLED}" == "yes" ]]; then
    sudo tee /etc/nginx/sites-available/kktc-erp.conf >/dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${CENTRAL_DOMAIN} *.${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${CENTRAL_DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    include /etc/nginx/snippets/kktc-erp-laravel.conf;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name *.${DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    include /etc/nginx/snippets/kktc-erp-laravel.conf;
}
EOF
    else
        sudo tee /etc/nginx/sites-available/kktc-erp.conf >/dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${CENTRAL_DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${CENTRAL_DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${CERT_PATH_DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${CERT_PATH_DOMAIN}/privkey.pem;

    include /etc/nginx/snippets/kktc-erp-laravel.conf;
}

server {
    listen 80;
    listen [::]:80;
    server_name *.${DOMAIN};

    include /etc/nginx/snippets/kktc-erp-laravel.conf;
}
EOF
    fi

    upsert_env "${ENV_FILE}" "APP_URL" "https://${CENTRAL_DOMAIN}"

    sudo mkdir -p /etc/letsencrypt/renewal-hooks/deploy
    sudo tee /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh >/dev/null <<'EOF'
#!/usr/bin/env bash
systemctl reload nginx
EOF
    sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
else
    error "SSL was expected but not enabled. Aborting."
    exit 1
fi

sudo ln -sf /etc/nginx/sites-available/kktc-erp.conf /etc/nginx/sites-enabled/kktc-erp.conf
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

log "Configuring scheduler cron..."
(crontab -l 2>/dev/null | grep -v "php artisan schedule:run"; echo "* * * * * cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

log "Configuring Supervisor queue worker..."
sudo mkdir -p /var/log/supervisor
sudo tee /etc/supervisor/conf.d/kktc-erp-worker.conf >/dev/null <<EOF
[program:kktc-erp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_PATH}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=${APP_PATH}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/kktc-erp-worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kktc-erp-worker:* || true

log "Installation completed."
echo
echo "Central URL : $(if [[ "${SSL_ENABLED}" == "yes" ]]; then echo "https"; else echo "http"; fi)://${CENTRAL_DOMAIN}"
echo "Tenant URL  : $(if [[ "${SSL_ENABLED}" == "yes" ]]; then echo "https"; else echo "http"; fi)://<tenant>.${DOMAIN}"
echo "App path    : ${APP_PATH}"
echo
echo "If seed was enabled, default admin user:"
if [[ "${RUN_SEED}" == "yes" ]]; then
echo "  Email: ${ADMIN_EMAIL}"
echo "  Password: (custom value entered during setup)"
else
echo "  Seed not run."
fi
echo
echo "Next manual checks:"
echo "  - php artisan tenants:list"
echo "  - php artisan tenants:migrate --force"
echo "  - sudo certbot renew --dry-run"
