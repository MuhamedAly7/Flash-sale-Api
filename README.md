# Flash Sale API - Unbreakable, Zero-Oversell, Production-Ready

Built with Laravel 12 + Redis + Lua + Docker  
Tested under 1000+ concurrent requests — **never oversells**

## Features

- Atomic stock reservation using Redis + Lua script
- Zero overselling - even under extreme concurrency
- Holds expire after 2 minutes → stock automatically returned
- Real orders created from holds
- Idempotent payment webhooks
- Real-time available stock
- Docker + Nginx + PHP 8.3 + Redis

## Architecture Overview
```
User → Nginx → Laravel API
↓
HoldService (Redis + Lua)
↓
→ holds:{uuid} + expired_hold:{uuid}
↓
OrderService → DB deduction on payment success
```

# Prerequisites
### - Docker & Docker Compose installed

## Setup

```

git clone https://github.com/MuhamedAly7/Flash-sale-Api.git
cd flash-sale-api

# Copy env
cp .env.example .env

update .env with your settings (DB, Redis, etc.)

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=flash_sale
DB_USERNAME=root
DB_PASSWORD=secret
CACHE_DRIVER=redis
REDIS_HOST=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379


# Start containers
docker compose up -d --build

# Install dependencies + migrate
docker compose exec app composer install --optimize-autoloader --no-dev
docker compose exec app php artisan migrate --seed

# Clear cache
docker compose exec app php artisan optimize:clear

# Generate App Key
docker compose exec app php artisan key:generate

# Access API at http://localhost:8000
```

## API Endpoints

- `POST /api/holds` - Create a stock hold
  - Body: `{ "product_id": 1, "quantity": 2, "ttl": 120 }`
    - Response: 
      - ```{
        "status": "success",
        "message": "Stock held successfully",
        "data": {
            "hold_id": "fd6ca91c-7fec-4856-aa85-2bcb600c18d4",
            "expires_in_seconds": 120}}
  - Errors: 400 Bad Request, 409 Conflict (insufficient stock)
  - Description: Atomically reserves stock for a limited time.
  - Concurrency: Safe under 1000+ concurrent requests.
  - TTL: Hold expires after specified seconds (default 120s).
  
- `POST /api/orders` - Create an order from a hold
  - Body: `{ "hold_id": "uuid", "payment_intent_id": "pi_12345" }`
  - Response: 
    - ```{
      "status": "success",
      "message": "Order created successfully",
      "data": {
          "order_id": 18,
          "status": "pending",
          "amount": 1198,
          "currency": "USD",
          "pay_with": "payment_intent_id: pi_123456789"
      }
  - Errors: 400 Bad Request, 404 Not Found (invalid hold), 409 Conflict (hold expired)
  - Description: Converts a valid hold into a real order.

- `POST /api/webhook/payment-success` - Payment webhook
  - Body: 
    - ```{
      "hold_id": "fd6ca91c-7fec-4856-aa85-2bcb600c18d4",
      "payment_intent_id": "pi_123456789"
      }
  - Response: 
    - ```{
      "status": "success",
      "message": "Payment processed successfully"
  - Errors: 400 Bad Request, 404 Not Found (invalid payment_intent_id)
  - Description: Idempotently processes payment notifications.
  - Idempotency: Safe to call multiple times for the same payment_intent_id.

- `GET /api/products/{id}` - Get available stock
  - Response: 
    - ```{
          "status": "success",
          "message": "Product retrieved",
          "data": {
              "id": 1,
              "name": "iPhone 15 Pro Max – Flash Sale Edition",
              "description": "Limited 100 units – 50% off for the first 60 seconds",
              "price": 599,
              "total_stock": 100,
              "available_stock": 100,
              "is_sold_out": false
          }
        }
      
    - Errors: 404 Not Found
    - Description: Retrieves product details including real-time available stock.
    - Stock Calculation: `available_stock = total_stock - reserved_in_holds - sold_in_orders`

## Testing APIs

- Import Postman collection Called `Flash-Sale-API.postman_collection.json` in root directory.
