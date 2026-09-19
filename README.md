# EcomClaude — Bangladesh B2C Ecommerce Platform

Laravel 13 single-store ecommerce architected for multi-tenant SaaS. COD-first
checkout, phone/OTP customer auth, BDT currency, and a Bangladesh address
structure (Division → District → Thana). PHP 8.4, Filament 3.3, Livewire 3.

See `CLAUDE.md` for architecture, data model, and conventions; `docs/BRS.md` /
`SRS.md` / `FRS.md` for requirements documentation.

## Requirements

- PHP 8.4, with extensions: `pdo_mysql`, `mbstring`, `exif`, `pcntl`,
  `bcmath`, `gd`, `zip`, `intl`, `opcache`
- Composer 2
- Node.js 20+ and npm
- MySQL 8+, running locally (not in Docker — see [Docker](#docker) below)

## Installation

```bash
git clone git@github-personal:jubaerhossainece/claude-ecom.git
cd claude-ecom

composer install
cp .env.example .env
php artisan key:generate

npm install
npm run build          # or `npm run dev` for hot reload while developing
```

Create a MySQL database and point `.env` at it — `.env.example` defaults to
sqlite, but this project runs on MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=claude_ecom
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

```bash
php artisan migrate:fresh --seed   # Bangladesh geo data, store settings, admin user, demo products
php artisan storage:link           # product/category/brand images are served from here
```

Start the app:

```bash
composer run dev   # serve + queue:listen + pail (log viewer) + vite, all in one terminal
```

or run each piece separately:

```bash
php artisan serve   # http://localhost:8000
npm run dev          # Tailwind/Vite hot reload, separate terminal
```

## Login

| Access | URL | Login |
|---|---|---|
| Admin panel (Filament) | `http://localhost:8000/admin` | `admin@store.test` / `password` |
| Customer storefront | `http://localhost:8000/login` | Phone + OTP (OTP is written to `storage/logs/laravel.log` in dev — no SMS gateway yet) |

Change the seeded admin password before using this anywhere but local dev.

## Docker

A container-parity / production Docker stack is also available (multi-stage
build, GHCR images, Traefik). See the "Docker" and "CI/CD" sections in
`CLAUDE.md`, and `DEPLOYMENT.md` for the full VPS deployment runbook.

## Testing

```bash
composer run test    # or: php artisan test
vendor/bin/pint --test
```
