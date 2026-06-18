# 🏭 Service 3: Warehouse Service

**Technology Stack:** Laravel 12 + PostgreSQL + **Hasura GraphQL** + RabbitMQ

---

## 📋 Description

Service untuk manajemen warehouse inventory. Menggunakan **Hasura GraphQL Engine** untuk auto-generated GraphQL dan Laravel REST API untuk operations.

## 🎯 Features

- ✅ Hasura GraphQL (Auto-generated)
- ✅ REST API for Warehouse Operations
- ✅ RabbitMQ Consumer (Warehouse Queue)
- ✅ Product Management
- ✅ Stock Management
- ✅ Low Stock Alerts

## 🔌 API Endpoints

### REST API
**Base URL:** `http://localhost:5000/api/v1`

- `GET /warehouse/orders` - Get all warehouse orders
- `GET /warehouse/products` - Get all products
- `GET /warehouse/stocks` - Get all stocks
- `GET /warehouse/low-stock` - Get low stock items
- `PATCH /warehouse/orders/{id}/status` - Update order status
- `POST /warehouse/dispatch/{trackingNumber}` - Dispatch order

### Hasura GraphQL
**Endpoint:** `http://localhost:8080/v1/graphql`
**Console:** `http://localhost:8080`
**Admin Secret:** `adminsecret123`

#### Example Queries:

```graphql
query GetAllProductsWithStock {
  products {
    id
    sku
    name
    description
    stocks {
      location
      quantity
      reorder_level
    }
  }
}

query GetWarehouseOrders {
  orders {
    reference
    status
    product {
      name
      sku
    }
  }
}
```

## 📦 RabbitMQ Events Consumed

- `order_created_queue` - Receives new orders from Order Service

## 🗄️ Database Schema

**PostgreSQL Database (Separate from Order DB)**

- `products` - Product catalog
- `stocks` - Stock inventory by location
- `orders` - Warehouse order records

## 🚀 Setup

```bash
# Install dependencies
composer install

# Copy .env
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Start consumer (in background)
php artisan rabbitmq:consume-warehouse
```

## 🐳 Docker

- REST API: Port **5000**
- Hasura Console: Port **8080**

## 📖 Hasura Setup

See `HASURA_SETUP.md` for complete Hasura configuration guide.

---

**Part of Fleet Delivery System - Tugas Besar IAE 2026**
