# 🚚 Fleet Delivery System - Microservices Architecture

> Tugas Besar Integrasi Aplikasi Enterprise (IAE)
> Sistem Logistik dan Pengiriman Barang berbasis Microservices

---

## 📋 Deskripsi Project

Fleet Delivery System adalah aplikasi enterprise berbasis microservices untuk mengelola sistem logistik dan pengiriman barang. Sistem ini terdiri dari 4 service yang terintegrasi menggunakan REST API, GraphQL (manual & Hasura), dan Message Broker (RabbitMQ).

### ✨ Fitur Utama
- **Manajemen Customer & Auth**: Register, login, dan profil pengirim/penerima
- **Order & Tracking**: Buat resi pengiriman dan lacak status real-time
- **Warehouse Management**: Kelola inventory dan status barang di gudang
- **Driver Assignment**: Otomatis assign kurir menggunakan message queue

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT / API GATEWAY                     │
└─────────────────────────────────────────────────────────────────┘
                                 │
        ┌────────────────────────┼────────────────────────┐
        │                        │                        │
        ▼                        ▼                        ▼
┌───────────────┐       ┌───────────────┐       ┌───────────────┐
│  Service 1    │       │  Service 2    │       │  Service 3    │
│  Auth & User  │       │ Order Service │       │   Warehouse   │
│               │       │               │       │               │
│  REST API     │       │ GraphQL (↓)   │       │ Hasura GraphQL│
│  (Laravel)    │       │  (Node.js)    │       │   (Laravel)   │
│               │       │               │       │               │
│  Port: 3000   │       │  Port: 4000   │       │  Port: 8080   │
└───────┬───────┘       └───────┬───────┘       └───────┬───────┘
        │                       │                       │
        │         ┌─────────────┼─────────────┬─────────┘
        │         │             │             │
        ▼         ▼             ▼             ▼
    ┌───────────────────────────────────────────────┐
    │            RabbitMQ (Message Broker)          │
    │                Port: 5672, 15672              │
    └───────────────────┬───────────────────────────┘
                        │
                        ▼
                ┌───────────────┐
                │  Service 4    │
                │ Driver Assign │
                │               │
                │ Consumer      │
                │  (Laravel)    │
                │               │
                │  Port: 6000   │
                └───────┬───────┘
                        │
        ┌───────────────┼───────────────┐
        ▼               ▼               ▼
   ┌─────────┐    ┌─────────┐    ┌─────────────┐
   │ MySQL   │    │Postgres │    │Postgres     │
   │ (Auth)  │    │(Order)  │    │(Warehouse)  │
   └─────────┘    └─────────┘    └─────────────┘
```

---

## 🎯 Service Details

### Service 1: Auth & Customer Service
- **Tech Stack**: Laravel 13 + MySQL + REST API + RabbitMQ Publisher
- **Port**: 3000
- **Fokus**: RESTful API Implementation
- **Database**: MySQL (terpisah)
- **Fitur**:
  - Register & Login (JWT via Sanctum)
  - Customer Management (CRUD)
  - Agent Management (CRUD)
  - Publish event ke RabbitMQ: `user_registered_queue`, `customer_created_queue`, `agent_created_queue`

### Service 2: Order & Tracking Service
- **Tech Stack**: Node.js + Apollo Server + PostgreSQL + RabbitMQ Publisher
- **Port**: 4000
- **Fokus**: GraphQL Manual Implementation
- **Database**: PostgreSQL (terpisah)
- **Fitur**:
  - GraphQL Query: `trackOrder(tracking_number)`
  - GraphQL Mutation: `createOrder`, `updateTracking`
  - Publish event ke RabbitMQ: `order_created_queue`
  - Real-time tracking history

### Service 3: Warehouse Service
- **Tech Stack**: Laravel 12 + PostgreSQL + Hasura GraphQL + RabbitMQ Consumer
- **Port REST API**: 5000
- **Port Hasura**: 8080
- **Fokus**: Hasura GraphQL Implementation
- **Database**: PostgreSQL (terpisah dari Order Service)
- **Fitur**:
  - GraphQL auto-generated dari Hasura untuk: Products, Stocks, Orders
  - REST API untuk monitoring: `/api/v1/warehouse/*`
  - Consumer RabbitMQ untuk menerima order masuk
  - Manajemen inventory (stock in/out)

### Service 4: Driver Assignment Service
- **Tech Stack**: Laravel 13 + PostgreSQL + RabbitMQ Consumer
- **Port**: 6000
- **Fokus**: Message Broker Implementation (Consumer)
- **Database**: PostgreSQL (shared dengan Order Service)
- **Fitur**:
  - **Consumer RabbitMQ**: Listen `order_created_queue`
  - Auto-assign driver ketika ada order baru
  - REST API Driver Management: `/api/drivers/*`
  - REST API Assignment: `/api/assignments/*`
  - Publish notifikasi ke `driver_assigned_queue`
  - Update tracking ke Order Service via GraphQL

---

## 🔄 Flow End-to-End

### Flow Lengkap Pengiriman Paket:

```
1. Customer Register/Login
   └─> Service 1 (REST) → MySQL
   
2. Customer Buat Order
   └─> Service 2 (GraphQL) → PostgreSQL
       └─> Publish ke RabbitMQ: order_created_queue
       
3. Warehouse Menerima Order
   └─> Service 3 (Consumer) mendengar order_created_queue
       └─> Record di warehouse DB via Hasura
       └─> Status: "received"
       
4. Driver Assignment (OTOMATIS)
   └─> Service 4 (Consumer) mendengar order_created_queue
       └─> Cari driver available
       └─> Assign driver ke order
       └─> Update status tracking via GraphQL ke Service 2
       └─> Publish ke driver_assigned_queue
       
5. Customer Tracking
   └─> Service 2 (GraphQL Query): trackOrder(tracking_number)
       └─> Return tracking history real-time
```

---

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Cara Menjalankan

1. **Clone Repository**
```bash
git clone <repository-url>
cd TUBES_EAI
```

2. **Setup Environment Files**
```bash
# Service 1 (Auth)
cd tugasbesar-IAE
cp .env.example .env

# Service 2 (Order)
cd ../Logistik-order-service
cp .env.example .env

# Service 3 (Warehouse)
cd ../warehouse-api
cp .env.example .env

# Service 4 (Driver)
cd ../tugasbesar-IAE-service-driver
cp .env.example .env

cd ..
```

3. **Build & Run dengan Docker Compose**
```bash
docker-compose up --build -d
```

4. **Run Migrations**
```bash
# Service 1 (Auth)
docker-compose exec auth-service php artisan migrate --seed

# Service 2 (Order) - Prisma
docker-compose exec order-service npx prisma migrate deploy

# Service 3 (Warehouse)
docker-compose exec warehouse-service php artisan migrate --seed

# Service 4 (Driver)
docker-compose exec driver-service php artisan migrate --seed
```

5. **Setup Hasura (Service 3)**
- Akses http://localhost:8080
- Login dengan admin secret: `adminsecret123`
- Track tables: `products`, `stocks`, `orders`
- Setup relationships (lihat file `warehouse-api/HASURA_SETUP.md`)

6. **Verify Services**
```bash
# Service 1 - Auth
curl http://localhost:3000/api/health

# Service 2 - Order (GraphQL)
curl http://localhost:4000/graphql

# Service 3 - Warehouse Hasura
curl http://localhost:8080/healthz

# Service 3 - Warehouse REST
curl http://localhost:5000/api/v1/warehouse/products

# Service 4 - Driver
curl http://localhost:6000/api/drivers

# RabbitMQ Management
open http://localhost:15672 (guest/guest)
```

---

## 📚 API Documentation

### Service 1: Auth & Customer (REST)
**Base URL**: `http://localhost:3000/api`

#### Auth Endpoints
```bash
POST /auth/register
POST /auth/login
GET  /auth/me (auth required)
POST /auth/logout (auth required)
```

#### Customer Endpoints
```bash
GET    /customers (auth required)
POST   /customers (auth required)
GET    /customers/{id} (auth required)
```

#### Agent Endpoints
```bash
GET    /agents (auth required)
POST   /agents (auth required)
GET    /agents/{id} (auth required)
```

### Service 2: Order & Tracking (GraphQL)
**Endpoint**: `http://localhost:4000/graphql`

#### GraphQL Queries
```graphql
query TrackOrder {
  trackOrder(tracking_number: "REG-1234567890-123") {
    id
    tracking_number
    customer_id
    item_description
    status
    created_at
    histories {
      id
      location
      description
      updated_at
    }
  }
}
```

#### GraphQL Mutations
```graphql
mutation CreateOrder {
  createOrder(
    customer_id: "CUST-001"
    item_description: "Paket Elektronik"
  ) {
    id
    tracking_number
    status
  }
}

mutation UpdateTracking {
  updateTracking(
    tracking_number: "REG-1234567890-123"
    location: "Jakarta Warehouse"
    description: "Paket telah sampai di gudang"
    status: "IN_WAREHOUSE"
  ) {
    id
    tracking_number
    status
  }
}
```

### Service 3: Warehouse (Hasura GraphQL + REST)

#### Hasura GraphQL
**Endpoint**: `http://localhost:8080/v1/graphql`
**Header**: `x-hasura-admin-secret: adminsecret123`

```graphql
query GetWarehouseInventory {
  products {
    id
    name
    sku
    stocks {
      location
      quantity
      reorder_level
    }
  }
}
```

Lihat dokumentasi lengkap di `warehouse-api/HASURA_SETUP.md`

#### Warehouse REST API
**Base URL**: `http://localhost:5000/api/v1`

```bash
GET  /warehouse/orders
GET  /warehouse/products
GET  /warehouse/stocks
GET  /warehouse/low-stock
PATCH /warehouse/orders/{id}/status
POST  /warehouse/dispatch/{trackingNumber}
```

### Service 4: Driver Assignment (REST)
**Base URL**: `http://localhost:6000/api`

#### Driver Endpoints
```bash
GET    /drivers
GET    /drivers/available
POST   /drivers
GET    /drivers/{id}
PUT    /drivers/{id}
DELETE /drivers/{id}
```

#### Assignment Endpoints
```bash
GET   /assignments
GET   /assignments/tracking/{trackingNumber}
GET   /assignments/driver/{driverId}
PATCH /assignments/{id}/status
```

---

## 🧪 Testing Flow Lengkap

### 1. Register Customer
```bash
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "role": "sender"
  }'
```

### 2. Create Order (GraphQL)
```bash
curl -X POST http://localhost:4000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation { createOrder(customer_id: \"john@example.com\", item_description: \"Laptop Gaming\") { id tracking_number status } }"
  }'
```

### 3. Check RabbitMQ
- Buka http://localhost:15672
- Login: guest/guest
- Lihat queue `order_created_queue` → Messages should be consumed

### 4. Verify Driver Assignment
```bash
# Check assigned driver
curl http://localhost:6000/api/assignments/tracking/REG-XXXXXXXXXX
```

### 5. Track Order
```bash
curl -X POST http://localhost:4000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query { trackOrder(tracking_number: \"REG-XXXXXXXXXX\") { tracking_number status histories { location description updated_at } } }"
  }'
```

### 6. Check Warehouse (Hasura)
```bash
curl -X POST http://localhost:8080/v1/graphql \
  -H "x-hasura-admin-secret: adminsecret123" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query { orders(where: {reference: {_eq: \"REG-XXXXXXXXXX\"}}) { reference status product { name } } }"
  }'
```

---

## 🐰 RabbitMQ Queues

| Queue Name | Publisher | Consumer | Purpose |
|------------|-----------|----------|---------|
| `order_created_queue` | Service 2 (Order) | Service 3 (Warehouse) + Service 4 (Driver) | Notifikasi order baru |
| `driver_assigned_queue` | Service 4 (Driver) | (Future: Notification Service) | Notifikasi driver assigned |
| `user_registered_queue` | Service 1 (Auth) | (Future: Email Service) | Notifikasi user baru |
| `customer_created_queue` | Service 1 (Auth) | (Future: CRM Service) | Sync customer data |
| `agent_created_queue` | Service 1 (Auth) | (Future: HR Service) | Sync agent data |

---

## 🗄️ Database Schema

### Service 1 - MySQL (Auth & User)
- `users` (id, name, email, password, role)
- `customers` (id, user_id, full_name, phone, address)
- `agents` (id, user_id, full_name, phone, branch_name, address)

### Service 2 - PostgreSQL (Order)
- `orders` (id, tracking_number, customer_id, item_description, status, created_at)
- `tracking_histories` (id, order_id, location, description, updated_at)

### Service 3 - PostgreSQL (Warehouse)
- `products` (id, sku, name, description, price, category)
- `stocks` (id, product_id, location, quantity, reorder_level)
- `orders` (id, reference, status, product_id, quantity, total)

### Service 4 - PostgreSQL (Driver - Shared with Order DB)
- `drivers` (id, name, phone_number, status)
- `driver_assignments` (id, order_id, driver_id, status)

---

## 📊 Technology Stack Summary

| Aspek | Teknologi |
|-------|-----------|
| **Service 1** | Laravel 13, MySQL, Sanctum, php-amqplib |
| **Service 2** | Node.js, Apollo Server, Prisma, PostgreSQL, amqplib |
| **Service 3** | Laravel 12, PostgreSQL, Hasura, php-amqplib |
| **Service 4** | Laravel 13, PostgreSQL, php-amqplib, Supervisor |
| **Message Broker** | RabbitMQ 3-management |
| **Containerization** | Docker, Docker Compose |
| **API Protocols** | REST, GraphQL (Manual), GraphQL (Hasura) |

---

## 🎓 Compliance dengan Requirement Dosen

### ✅ Docker Deployment
- Semua service running dalam container terpisah
- `docker-compose.yml` untuk orchestration
- Database terpisah per service

### ✅ RESTful API
- Service 1 (Auth & Customer): Full REST API
- Service 3 (Warehouse): REST API untuk monitoring
- Service 4 (Driver): REST API untuk management

### ✅ GraphQL Manual (Backend Framework)
- Service 2 (Order): Apollo Server + Node.js
- Implementasi resolver manual
- Schema TypeScript

### ✅ GraphQL Hasura
- Service 3 (Warehouse): Hasura GraphQL Engine
- Auto-generated dari database schema
- Supports relationships & filtering

### ✅ Message Broker
- RabbitMQ untuk async communication
- Publisher: Service 1, 2
- Consumer: Service 3, 4
- Queue-based event-driven architecture

### ✅ Microservices Architecture
- 4 services terpisah (1 per anggota kelompok)
- Database terpisah
- Independent deployment
- Loose coupling via message queue

---

## 👥 Team Members

| Nama | Service | Fokus |
|------|---------|-------|
| Member 1 | Auth & Customer | REST API + RabbitMQ Publisher |
| Member 2 | Order & Tracking | GraphQL Manual + RabbitMQ |
| Member 3 | Warehouse | Hasura GraphQL + Consumer |
| Member 4 | Driver Assignment | Message Broker Consumer + Auto Assignment |

---

## 📝 Dokumentasi Tambahan

- [Hasura Setup Guide](./warehouse-api/HASURA_SETUP.md)
- [Postman Collection](#) (link to Postman workspace)
- [Architecture Diagram](#) (link to diagram)

---

## 🐛 Troubleshooting

### RabbitMQ Connection Refused
```bash
# Check RabbitMQ is running
docker-compose ps rabbitmq

# Restart RabbitMQ
docker-compose restart rabbitmq
```

### Database Connection Error
```bash
# Check database is running
docker-compose ps postgres-db

# Run migrations again
docker-compose exec <service-name> php artisan migrate
```

### Service Not Responding
```bash
# Check logs
docker-compose logs <service-name>

# Restart specific service
docker-compose restart <service-name>
```

---

## 📄 License

This project is for educational purposes only.

**Tugas Besar Integrasi Aplikasi Enterprise - 2026**
