# Inventory and Order API

A Laravel 12 REST API for listing inventory and placing orders safely, backed by MySQL.

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL 8 or newer

## Setup

```bash
git clone https://github.com/hardeep-97/mediccapress-assignment.git
cd mediccapress-assignment
composer install
cp .env.example .env
php artisan key:generate
```

Create the MySQL database:

```sql
CREATE DATABASE inventory_order_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`, then prepare the data and start the API:

```bash
php artisan migrate --seed
php artisan serve
```

The API is available at `http://127.0.0.1:8000`.

Start the database queue worker in a separate terminal:

```bash
php artisan queue:work --tries=3
```

The worker processes the queued receipt listener after an order transaction commits. Keep it running while creating orders.

## Endpoints

### List products

```bash
curl "http://127.0.0.1:8000/api/products?in_stock=1&per_page=15" \
  -H "Accept: application/json"
```

Query parameters:

| Parameter | Description |
| --- | --- |
| `in_stock` | Set to `1` to return only products whose `stock_quantity` is greater than zero. |
| `per_page` | Results per page, from 1 to 100. Defaults to 15. |

### Create an order

```bash
curl -X POST "http://127.0.0.1:8000/api/orders" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "buyer@example.com",
    "items": [
      {"product_id": 1, "quantity": 2},
      {"product_id": 2, "quantity": 1}
    ]
  }'
```

A successful request returns `201 Created`. Invalid data and insufficient stock return `422 Unprocessable Entity` with field-level details.

## Implementation notes

- Product prices are converted to integer cents for calculations, avoiding floating-point rounding errors.
- Order creation runs in one database transaction.
- Product rows are locked in a consistent order with `lockForUpdate()` before stock is checked or changed, preventing concurrent requests from overselling inventory.
- Order item unit prices and subtotals are stored as a purchase-time snapshot.
- `OrderPlaced` implements Laravel's after-commit event contract, so it is not dispatched for a rolled-back transaction.
- The receipt listener is queued after commit, retries failures with backoff, and writes a formatted receipt with item details to `storage/logs/laravel.log`.
