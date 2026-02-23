#!/usr/bin/env bash

set -euo pipefail

log() {
    echo -e "\033[1;34m[INFO]\033[0m $*"
}

error() {
    echo -e "\033[1;31m[ERROR]\033[0m $*" >&2
}

usage() {
    cat <<'EOF'
Usage:
  bash scripts/create-superadmin.sh --email admin@domain.com --username superadmin --password 'StrongPass123!'

Optional:
  --app-path /var/www/kktc-erp-saas   Laravel app path (default: current dir if artisan exists, else /var/www/kktc-erp-saas)
  --run-as  www-data                  OS user to run artisan commands (default: www-data)
  --help                              Show help
EOF
}

APP_PATH=""
RUN_AS="www-data"
ADMIN_EMAIL=""
ADMIN_USERNAME="superadmin"
ADMIN_PASSWORD=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --app-path)
            APP_PATH="${2:-}"
            shift 2
            ;;
        --run-as)
            RUN_AS="${2:-}"
            shift 2
            ;;
        --email)
            ADMIN_EMAIL="${2:-}"
            shift 2
            ;;
        --username)
            ADMIN_USERNAME="${2:-}"
            shift 2
            ;;
        --password)
            ADMIN_PASSWORD="${2:-}"
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            error "Unknown argument: $1"
            usage
            exit 1
            ;;
    esac
done

if [[ -z "${ADMIN_EMAIL}" || -z "${ADMIN_PASSWORD}" ]]; then
    error "--email and --password are required."
    usage
    exit 1
fi

if [[ -z "${APP_PATH}" ]]; then
    if [[ -f "./app/artisan" ]]; then
        APP_PATH="$(cd ./app && pwd)"
    elif [[ -f "./artisan" ]]; then
        APP_PATH="$(pwd)"
    else
        APP_PATH="/var/www/kktc-erp-saas"
    fi
fi

if [[ ! -f "${APP_PATH}/artisan" ]]; then
    error "artisan not found at ${APP_PATH}"
    exit 1
fi

if ! id "${RUN_AS}" >/dev/null 2>&1; then
    error "OS user not found: ${RUN_AS}"
    exit 1
fi

log "Creating/updating super-admin user in ${APP_PATH}"

cd "${APP_PATH}"

sudo -u "${RUN_AS}" env \
    ADMIN_EMAIL="${ADMIN_EMAIL}" \
    ADMIN_USERNAME="${ADMIN_USERNAME}" \
    ADMIN_PASSWORD="${ADMIN_PASSWORD}" \
    php artisan tinker --execute='
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$email = strtolower(trim((string) getenv("ADMIN_EMAIL")));
$username = strtolower(trim((string) getenv("ADMIN_USERNAME")));
$password = (string) getenv("ADMIN_PASSWORD");

Role::findOrCreate("super-admin", "web");

$user = User::query()
    ->where("username", $username)
    ->orWhere("email", $email)
    ->first();

if (! $user) {
    $user = User::query()->create([
        "name" => "Super Admin",
        "username" => $username,
        "email" => $email,
        "password" => Hash::make($password),
    ]);
} else {
    $user->username = $username;
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->two_factor_secret = null;
    $user->two_factor_recovery_codes = null;
    $user->two_factor_confirmed_at = null;
    $user->save();
}

$user->syncRoles(["super-admin"]);

echo "Super-admin ready: ".$user->email.PHP_EOL;
'

log "Done."
