# FAZ 2 - Tenancy ve Merkez Veritabani Mimarisi

Bu dokuman, Laravel 11 projesinde **stancl/tenancy** ve **spatie/laravel-permission** kurulumunu ve merkezi (central) veritabani semasini aciklar.

> Proje dizini: `/workspace/app`

## 1) Paket kurulumu

```bash
cd /workspace/app
composer require stancl/tenancy spatie/laravel-permission
php artisan tenancy:install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Uretilen ana dosyalar:
- `config/tenancy.php`
- `routes/tenant.php`
- `app/Providers/TenancyServiceProvider.php`
- `database/migrations/2019_09_15_000010_create_tenants_table.php`
- `database/migrations/2019_09_15_000020_create_domains_table.php`
- `database/migrations/*_create_permission_tables.php`

## 2) Central DB yapisi (bu fazda eklenenler)

### users tablosu
- `username` (unique)
- `two_factor_secret` (nullable)
- `two_factor_recovery_codes` (nullable)
- `two_factor_confirmed_at` (nullable timestamp)

### tenants tablosu
- `reseller_id` (nullable FK -> `users.id`)

### domains tablosu
- tenant domain eslesmelerini tutar (`domain`, `tenant_id`)

### Spatie Permission tablolari (central)
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

## 3) Konfigurasyon notlari

- `config/database.php`
  - varsayilan baglanti `central`
  - `central` ve `tenant` baglantilari tanimlandi
- `config/tenancy.php`
  - `tenant_model` => `App\Models\Tenant`
  - `central_domains` env uzerinden okunur (`CENTRAL_DOMAINS`)
  - DB adi sablonu env uzerinden okunur (`TENANCY_DB_PREFIX`, `TENANCY_DB_SUFFIX`)
  - `central_connection` => `DB_CENTRAL_CONNECTION`
- `bootstrap/providers.php`
  - `App\Providers\TenancyServiceProvider::class` eklendi

## 4) Migration calistirma akisi

### Central migrationlar
```bash
php artisan migrate --database=central
```

### Tenant migrationlari (tenant DB'lerde)
> Bu fazda tenant migration dosyalari henuz yazilmadi (FAZ 4'te yazilacak).

```bash
php artisan tenants:migrate
```

## 5) Model iliskileri (bu faz)

- `App\Models\User`
  - `HasRoles` (spatie)
  - `tenants()` -> `hasMany(App\Models\Tenant, 'reseller_id')`

- `App\Models\Tenant`
  - `HasDatabase`, `HasDomains` (stancl)
  - `reseller()` -> `belongsTo(App\Models\User, 'reseller_id')`

