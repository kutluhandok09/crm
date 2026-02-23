# FAZ 1 - Ubuntu Server 22.04/24.04 Kurulum Rehberi (LEMP + Wildcard SSL + Nginx Wildcard)

Bu rehber, Laravel 11 tabanli Multi-Tenant SaaS ERP projesini Ubuntu uzerinde calistirmak icin adim adim kurulum komutlarini icerir.

Hedef mimari:
- **Merkez panel**: `admin.domain.com`
- **Firma panelleri (tenant)**: `*.domain.com`
- **Web server**: Nginx
- **Runtime**: PHP 8.2+ (onerilen 8.3)
- **DB**: MySQL 8
- **SSL**: Let's Encrypt Wildcard (`*.domain.com`)

> Not: Komutlarda `domain.com` ve IP gibi alanlari kendi degerlerinle degistir.

---

## 0) Degiskenleri tanimla (ornek)

```bash
export APP_DOMAIN="domain.com"
export CENTRAL_DOMAIN="admin.${APP_DOMAIN}"
export APP_PATH="/var/www/erp-saas"
export PHP_VER="8.3"
```

---

## 1) DNS hazirligi (zorunlu)

DNS panelinde asagidakileri tanimla:

- `A` kaydi: `admin.domain.com` -> Sunucu IP
- `A` kaydi: `*.domain.com` -> Sunucu IP (wildcard)
- (Opsiyonel) `A` kaydi: `domain.com` -> Sunucu IP

Wildcard SSL icin DNS provider API kullanacagiz (Cloudflare ornegi verildi).

---

## 2) Sunucu temel hazirlik

```bash
sudo apt update && sudo apt upgrade -y
sudo timedatectl set-timezone Europe/Istanbul
sudo apt install -y software-properties-common ca-certificates curl gnupg lsb-release unzip git ufw
```

Firewall:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
sudo ufw status
```

---

## 3) Nginx kurulumu

```bash
sudo apt install -y nginx
sudo systemctl enable --now nginx
sudo systemctl status nginx --no-pager
```

---

## 4) PHP 8.2+ (onerilen 8.3) ve gerekli extensionlar

Ubuntu 22.04'te varsayilan PHP 8.1 olabilir. 8.2+ icin PPA kullan:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
  php${PHP_VER} php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
  php${PHP_VER}-mysql php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-curl \
  php${PHP_VER}-zip php${PHP_VER}-bcmath php${PHP_VER}-intl php${PHP_VER}-gd \
  php${PHP_VER}-redis php${PHP_VER}-soap
```

Kontrol:

```bash
php -v
sudo systemctl enable --now php${PHP_VER}-fpm
sudo systemctl status php${PHP_VER}-fpm --no-pager
```

---

## 5) MySQL 8 kurulumu

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
sudo systemctl status mysql --no-pager
sudo mysql_secure_installation
```

Merkez (central) veritabani ve uygulama kullanicisi:

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS erp_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'erp_app'@'127.0.0.1' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"
sudo mysql -e "GRANT ALL PRIVILEGES ON erp_central.* TO 'erp_app'@'127.0.0.1';"
```

Eger **database-per-tenant** kullanacaksan (stancl/tenancy), tenant DB olusturabilmesi icin:

```bash
sudo mysql -e "GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON *.* TO 'erp_app'@'127.0.0.1';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

> Daha sikilik istersen tenant DB olusturma yetkisini ayri bir DB kullanicisiyla da yonetebilirsin.

---

## 6) Composer kurulumu

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
HASH="$(curl -sS https://composer.github.io/installer.sig)"
php -r "if (hash_file('sha384', 'composer-setup.php') === '${HASH}') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php
composer --version
```

---

## 7) Node.js (LTS) ve npm kurulumu

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 8) Laravel uygulama dizini ve izinler

```bash
sudo mkdir -p "${APP_PATH}"
sudo chown -R $USER:www-data "${APP_PATH}"
sudo chmod -R 775 "${APP_PATH}"
```

Proje kodu bu dizine geldikten sonra:

```bash
cd "${APP_PATH}"
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan storage:link
```

Laravel yazma izinleri:

```bash
sudo chown -R www-data:www-data "${APP_PATH}/storage" "${APP_PATH}/bootstrap/cache"
sudo chmod -R 775 "${APP_PATH}/storage" "${APP_PATH}/bootstrap/cache"
```

---

## 9) Certbot + Wildcard SSL (DNS Challenge, Cloudflare ornegi)

Wildcard sertifika **HTTP challenge ile alinmaz**, DNS challenge gerekir.

```bash
sudo apt install -y certbot python3-certbot-dns-cloudflare
sudo mkdir -p /etc/letsencrypt
sudo bash -c 'cat > /etc/letsencrypt/cloudflare.ini <<EOF
dns_cloudflare_api_token = CLOUDFLARE_API_TOKEN_HERE
EOF'
sudo chmod 600 /etc/letsencrypt/cloudflare.ini
```

Sertifika alma:

```bash
sudo certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
  -d "${APP_DOMAIN}" \
  -d "*.${APP_DOMAIN}" \
  --agree-tos -m admin@"${APP_DOMAIN}" --no-eff-email
```

---

## 10) Nginx wildcard server block (admin + tenant)

### 10.1 Ortak Laravel snippet dosyasi

```bash
sudo bash -c "cat > /etc/nginx/snippets/laravel-common.conf <<'EOF'
root /var/www/erp-saas/public;
index index.php index.html;

location / {
    try_files \$uri \$uri/ /index.php?\$query_string;
}

location = /favicon.ico { access_log off; log_not_found off; }
location = /robots.txt  { access_log off; log_not_found off; }

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
}

location ~ /\.(?!well-known).* {
    deny all;
}
EOF"
```

> Eger PHP versiyonun farkliysa `php8.3-fpm.sock` satirini uygun surume cevir (ornek: `php8.2-fpm.sock`).

### 10.2 Site konfigurasyonu

```bash
sudo bash -c "cat > /etc/nginx/sites-available/erp-saas.conf <<'EOF'
# HTTP -> HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name admin.domain.com *.domain.com;
    return 301 https://\$host\$request_uri;
}

# Central Panel
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name admin.domain.com;

    ssl_certificate     /etc/letsencrypt/live/domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    include /etc/nginx/snippets/laravel-common.conf;
}

# Tenant wildcard (*.domain.com)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name *.domain.com;

    ssl_certificate     /etc/letsencrypt/live/domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    include /etc/nginx/snippets/laravel-common.conf;
}
EOF"
```

`domain.com` alanlarini degistir:

```bash
sudo sed -i "s/domain.com/${APP_DOMAIN}/g" /etc/nginx/sites-available/erp-saas.conf
sudo sed -i "s/admin.${APP_DOMAIN}/${CENTRAL_DOMAIN}/g" /etc/nginx/sites-available/erp-saas.conf
```

Site aktif et:

```bash
sudo ln -sf /etc/nginx/sites-available/erp-saas.conf /etc/nginx/sites-enabled/erp-saas.conf
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 11) Certbot otomatik yenileme

Test:

```bash
sudo certbot renew --dry-run
```

Yenileme sonrasi Nginx reload hook:

```bash
sudo mkdir -p /etc/letsencrypt/renewal-hooks/deploy
sudo bash -c 'cat > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh <<EOF
#!/usr/bin/env bash
systemctl reload nginx
EOF'
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
```

---

## 12) Laravel scheduler ve queue (onerilen)

Cron:

```bash
(crontab -l 2>/dev/null; echo "* * * * * cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

Queue worker icin supervisor:

```bash
sudo apt install -y supervisor
sudo systemctl enable --now supervisor
```

---

## 13) Hizli dogrulama kontrol listesi

1. `php -v` -> 8.2+
2. `mysql --version` -> 8.x
3. `nginx -t` -> successful
4. `curl -I https://admin.domain.com` -> 200/302
5. `curl -I https://firma1.domain.com` -> 200/302 (tenant domain)
6. `sudo certbot renew --dry-run` -> success

---

## 14) Stancl Tenancy icin kritik not

Nginx wildcard yalnizca istekleri Laravel'e iletir. Hangi host'un central/tenant olduguna **Laravel + stancl/tenancy** karar verir.

FAZ 2'de su alanlar kod tarafinda tanimlanacak:
- central domains (`admin.domain.com`)
- tenants ve domains tablolari
- tenant DB olusturma akisi
- ACL (spatie/permission) merkezi DB'de

