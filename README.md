# Laundry Management System

A secure Laravel 13 REST API for laundry services, customer orders, fulfillment tracking, and payments.

## Features

- Sanctum authentication with throttled login and registration
- Admin, staff, and customer roles with ownership policies
- Configurable per-kilogram or per-item services and turnaround times
- Customer-scoped, searchable, paginated orders with unguessable tracking numbers
- Server-side price snapshots and due-date calculation
- Audited `received -> washing -> drying -> ready -> claimed` workflow
- Transactional payments with unique references, balance checks, and claim protection
- Dashboard metrics, structured JSON errors, CORS allowlist, Docker, and CI

## Quick start

Requires PHP 8.3+, Composer, and MySQL 8.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Seeded accounts use password `ChangeMe123!`: `admin@example.com` and `customer@example.com`. Change these immediately outside local development.

## API

All routes use `/api`. Public endpoints register and authenticate users. Customers browse services and manage only their own orders. Staff/admin users manage services, advance order status, record payments, and access `/dashboard`.

```json
{
  "service_type_id": 1,
  "quantity": 4.5,
  "notes": "Use fragrance-free detergent"
}
```

## Docker and checks

Generate a key with `php artisan key:generate --show`, replace the Compose placeholder, and run `docker compose up --build`.

```bash
composer test
vendor/bin/pint --test
composer audit
```

CI additionally validates migrations and seeders against MySQL 8.4.

## License

MIT
