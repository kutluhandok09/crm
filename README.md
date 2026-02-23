# KKTC ERP SaaS (Multi-Tenant Laravel 11)

Bu repo, KKTC ticari ihtiyaclari icin tasarlanmis, **multi-tenant (coklu kiraci)** mimariye sahip SaaS ERP/CRM uygulamasini icerir.

## Ozellik Ozet

- Laravel 11, PHP 8.2+
- Multi-Tenant altyapi: `stancl/tenancy`
- Merkez ACL/Yetki yonetimi: `spatie/laravel-permission`
- 2FA (TOTP + Recovery Code): `pragmarx/google2fa-laravel`
- Mobil barkod/kamera tarama: `html5-qrcode`
- Merkezi panel:
  - Super Admin
  - Bayi (Reseller)
  - Firma (Tenant) olusturma/yetkilendirme
- Tenant panel:
  - Urun + Seri Numarasi yonetimi
  - Alis/Satis faturalama
  - Kur kaydi, KDV, finans transaction altyapisi

---

## Proje Yapisi

- Laravel uygulamasi: `app/`
- Tenant migrationlari: `app/database/migrations/tenant/`
- Merkezi migrationlar: `app/database/migrations/`
- Ubuntu kurulum scripti: `scripts/ubuntu-install.sh`

---

## Teknoloji ve Veritabani Notu

Proje **MariaDB ile calisacak sekilde** duzenlenmistir (default):

- `DB_CENTRAL_DRIVER=mariadb`
- `DB_TENANT_DRIVER=mariadb`
- Tenancy manager icinde `mariadb` destegi aktiftir.

Istersen `mysql` de kullanabilirsin; `.env` degiskenleri ile degistirilir.

---

## Hizli Kurulum (Ubuntu 22.04 / 24.04)

### 1) Scripti calistir

Repo kok dizininde:

```bash
bash scripts/ubuntu-install.sh
```

Script senden su bilgileri ister:

- ana domain (`domain.com`)
- central subdomain (`admin`)
- app kurulum yolu (`/var/www/kktc-erp-saas`)
- PHP surumu (onerilen `8.3`)
- Node LTS surumu (onerilen `20`)
- MariaDB veritabani bilgileri
- tenant veritabani prefix
- SSL modu:
  - `wildcard-cloudflare` (onerilen, tenant wildcard SSL icin)
  - `central-only` (sadece merkezi domain SSL)
- wildcard mod secilirse Cloudflare API token (zorunlu)

### 2) Script ne yapar?

- LEMP gereksinimlerini kurar: Nginx, PHP, MariaDB, Composer, Node.js
- UFW ayarlarini yapar
- Laravel kodunu hedef dizine deploy eder
- `.env` dosyasini MariaDB + Tenancy icin doldurur
- Central DB olusturur ve yetki verir
- Migration + (opsiyonel) seed calistirir
- Nginx merkezi + wildcard domain konfigurasyonunu yazar
- SSL sertifikasi olusturur:
  - wildcard modda `domain.com` + `*.domain.com`
  - central-only modda sadece `admin.domain.com`
- Cron scheduler ve Supervisor queue worker kurar

---

## Manuel Gelistirme (lokal)

```bash
cd app
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --database=central
npm run build
php artisan serve
```

---

## Ilk Giris

Seed acildiysa script kurulum sirasinda senden **ilk super-admin email ve sifresini** ister ve o degerleri uygular.

---

## Onemli Operasyon Komutlari

### Central migration

```bash
cd app
php artisan migrate --database=central --force
```

### Tenant migration (tum tenant DB'leri)

```bash
cd app
php artisan tenants:migrate --force
```

### Testler

```bash
cd app
DB_CONNECTION=sqlite DB_CENTRAL_CONNECTION=sqlite DB_DATABASE=$(pwd)/database/database.sqlite php artisan test
```

---

## Uretim Notlari

- `APP_DEBUG=false`
- Queue worker Supervisor ile surekli calismali
- `php artisan schedule:run` cron ile dakikalik calismali
- SSL yenileme hook sonrasi Nginx reload edilmelidir
- Central domain ile tenant domain ayri oldugu icin DNS wildcard dogru ayarlanmalidir

