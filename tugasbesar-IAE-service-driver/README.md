# 🚗 Service 4: Driver Assignment Service

**Technology Stack:** Laravel 13 + PostgreSQL + RabbitMQ Consumer + Supervisor

---

## 📋 Description

Service untuk auto-assignment driver ke order baru. Menggunakan **RabbitMQ Consumer** yang berjalan sebagai background process untuk listen events dan assign driver otomatis.

## 🎯 Features

- ✅ RabbitMQ Consumer (Auto Driver Assignment)
- ✅ Driver Management (REST API)
- ✅ Assignment Tracking (REST API)
- ✅ Auto-update Tracking ke Order Service
- ✅ Notification Publishing

## 🔌 API Endpoints

**Base URL:** `http://localhost:6000/api`

### Driver Management
- `GET /drivers` - Get all drivers
- `GET /drivers/available` - Get available drivers
- `POST /drivers` - Create driver
- `GET /drivers/{id}` - Get driver by ID
- `PUT /drivers/{id}` - Update driver
- `DELETE /drivers/{id}` - Delete driver

### Assignment Management
- `GET /assignments` - Get all assignments
- `GET /assignments/tracking/{trackingNumber}` - Get assignment by tracking number
- `GET /assignments/driver/{driverId}` - Get assignments by driver
- `PATCH /assignments/{id}/status` - Update assignment status

## 📦 RabbitMQ

### Events Consumed
- `order_created_queue` - Receives new orders for driver assignment

### Events Published
- `driver_assigned_queue` - Publishes driver assignment notifications

## 🤖 Background Consumer

Consumer runs automatically via Supervisor:
```bash
# Manual run (for testing)
php artisan rabbitmq:consume-orders

# In Docker, runs automatically via supervisord
```

## 🔄 Auto Assignment Flow

1. Listen to `order_created_queue`
2. Find available driver
3. Create assignment record
4. Update driver status to 'busy'
5. Update tracking in Order Service (GraphQL)
6. Publish notification to `driver_assigned_queue`

## 🗄️ Database Schema

**PostgreSQL Database (Shared with Order Service)**

- `drivers` - Driver information (name, phone, status)
- `driver_assignments` - Assignment records

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

# Create test drivers
php artisan tinker
```

In tinker:
```php
App\Models\Driver::create(['name' => 'Ahmad', 'phone_number' => '0811111', 'status' => 'available']);
App\Models\Driver::create(['name' => 'Siti', 'phone_number' => '0822222', 'status' => 'available']);
```

## 🐳 Docker

Service runs on port **6000** in Docker Compose.
Consumer runs automatically via Supervisor.

## 📊 Monitor Consumer

```bash
# View consumer logs
docker-compose logs -f driver-service

# Check if consumer is running
docker-compose exec driver-service ps aux | grep rabbitmq
```

---

**Part of Fleet Delivery System - Tugas Besar IAE 2026**
