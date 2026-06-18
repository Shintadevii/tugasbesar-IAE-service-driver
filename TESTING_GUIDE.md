# 🧪 Testing Guide - Fleet Delivery System

Panduan lengkap untuk testing semua fitur sistem.

---

## 🚀 Quick Setup Commands

### 1. Initial Setup (Pertama Kali)
```bash
# Copy semua .env files
cp tugasbesar-IAE/.env.example tugasbesar-IAE/.env
cp Logistik-order-service/.env.example Logistik-order-service/.env
cp warehouse-api/.env.example warehouse-api/.env
cp tugasbesar-IAE-service-driver/.env.example tugasbesar-IAE-service-driver/.env

# Build dan jalankan semua services
docker-compose up --build -d

# Wait for services to be ready (tunggu 30 detik)

# Run migrations
docker-compose exec auth-service php artisan key:generate
docker-compose exec auth-service php artisan migrate --seed

docker-compose exec order-service npx prisma migrate deploy

docker-compose exec warehouse-service php artisan key:generate
docker-compose exec warehouse-service php artisan migrate --seed

docker-compose exec driver-service php artisan key:generate
docker-compose exec driver-service php artisan migrate --seed
```

### 2. Start/Stop Services
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart <service-name>

# View logs
docker-compose logs -f <service-name>
```

### 3. Check Service Health
```bash
# Check all running containers
docker-compose ps

# Service 1 - Auth (should return 404 or Laravel error page)
curl http://localhost:3000

# Service 2 - Order GraphQL
curl http://localhost:4000/graphql

# Service 3 - Warehouse
curl http://localhost:5000/api/v1/warehouse/products

# Service 3 - Hasura
curl http://localhost:8080/healthz

# Service 4 - Driver
curl http://localhost:6000/api/drivers

# RabbitMQ Management UI
# Open browser: http://localhost:15672 (guest/guest)
```

---

## 📝 Testing Scenarios

### Scenario 1: Complete Order Flow

#### Step 1: Register Customer
```bash
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "password": "password123",
    "role": "sender"
  }'
```

**Expected Response:**
```json
{
  "message": "Register success",
  "token": "1|abcd1234...",
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "sender"
  }
}
```

**Copy the token for next requests!**

#### Step 2: Create Customer Profile
```bash
curl -X POST http://localhost:3000/api/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "full_name": "Budi Santoso",
    "phone": "08123456789",
    "address": "Jl. Sudirman No. 123, Jakarta"
  }'
```

#### Step 3: Create Order (GraphQL)
```bash
curl -X POST http://localhost:4000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation CreateOrder($customerId: String!, $itemDesc: String!) { createOrder(customer_id: $customerId, item_description: $itemDesc) { id tracking_number customer_id item_description status created_at histories { id location description updated_at } } }",
    "variables": {
      "customerId": "budi@example.com",
      "itemDesc": "Laptop Gaming ASUS ROG"
    }
  }'
```

**Expected Response:**
```json
{
  "data": {
    "createOrder": {
      "id": "1",
      "tracking_number": "REG-1718234567890-123",
      "customer_id": "budi@example.com",
      "item_description": "Laptop Gaming ASUS ROG",
      "status": "PENDING",
      "created_at": "2026-06-18T10:30:00.000Z",
      "histories": [
        {
          "id": "1",
          "location": "Gudang Pengirim",
          "description": "Pesanan telah dibuat dan menunggu pick-up kurir.",
          "updated_at": "2026-06-18T10:30:00.000Z"
        }
      ]
    }
  }
}
```

**🎯 Important: Copy the tracking_number!**

#### Step 4: Check RabbitMQ (Order Published)
1. Open http://localhost:15672
2. Login: guest/guest
3. Go to "Queues" tab
4. Check `order_created_queue`
5. Should see message count decreasing (consumed by Warehouse & Driver services)

#### Step 5: Verify Warehouse Received Order
```bash
# Via REST API
curl http://localhost:5000/api/v1/warehouse/orders

# Via Hasura GraphQL
curl -X POST http://localhost:8080/v1/graphql \
  -H "x-hasura-admin-secret: adminsecret123" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query { orders { id reference status product { name sku } created_at } }"
  }'
```

#### Step 6: Verify Driver Assignment
```bash
# Check if driver was assigned
curl http://localhost:6000/api/assignments/tracking/REG-1718234567890-123
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": "REG-1718234567890-123",
    "driver_id": 1,
    "status": "assigned",
    "driver": {
      "id": 1,
      "name": "Driver Default 42",
      "phone_number": "081234567890",
      "status": "busy"
    }
  }
}
```

#### Step 7: Track Order Status
```bash
curl -X POST http://localhost:4000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query TrackOrder($trackingNumber: String!) { trackOrder(tracking_number: $trackingNumber) { id tracking_number customer_id item_description status created_at histories { id location description updated_at } } }",
    "variables": {
      "trackingNumber": "REG-1718234567890-123"
    }
  }'
```

**Expected: Should show tracking history including driver assignment!**

#### Step 8: Update Tracking (Driver pickup)
```bash
curl -X POST http://localhost:4000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation UpdateTracking($trackingNumber: String!, $location: String!, $description: String!, $status: String!) { updateTracking(tracking_number: $trackingNumber, location: $location, description: $description, status: $status) { id tracking_number status histories { location description updated_at } } }",
    "variables": {
      "trackingNumber": "REG-1718234567890-123",
      "location": "Jakarta Pusat",
      "description": "Paket sedang dalam perjalanan",
      "status": "IN_TRANSIT"
    }
  }'
```

#### Step 9: Dispatch from Warehouse
```bash
curl -X POST http://localhost:5000/api/v1/warehouse/dispatch/REG-1718234567890-123
```

---

### Scenario 2: Driver Management

#### Create Multiple Drivers
```bash
# Driver 1
curl -X POST http://localhost:6000/api/drivers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ahmad Kurir",
    "phone_number": "08111111111",
    "status": "available"
  }'

# Driver 2
curl -X POST http://localhost:6000/api/drivers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Siti Kurir",
    "phone_number": "08122222222",
    "status": "available"
  }'
```

#### Get Available Drivers
```bash
curl http://localhost:6000/api/drivers/available
```

#### Update Driver Status
```bash
curl -X PUT http://localhost:6000/api/drivers/1 \
  -H "Content-Type: application/json" \
  -d '{
    "status": "available"
  }'
```

---

### Scenario 3: Warehouse Operations

#### Get All Products
```bash
curl http://localhost:5000/api/v1/warehouse/products
```

#### Check Low Stock
```bash
curl http://localhost:5000/api/v1/warehouse/low-stock
```

#### Query via Hasura (Advanced)
```bash
curl -X POST http://localhost:8080/v1/graphql \
  -H "x-hasura-admin-secret: adminsecret123" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query GetLowStock { stocks(where: {quantity: {_lte: 5}}) { id location quantity reorder_level product { name sku category } } }"
  }'
```

---

## 🐛 Common Issues & Solutions

### Issue 1: RabbitMQ Connection Refused
```bash
# Solution: Restart RabbitMQ
docker-compose restart rabbitmq

# Wait 10 seconds, then restart consumers
docker-compose restart driver-service
docker-compose restart warehouse-service
```

### Issue 2: Database Connection Error
```bash
# Solution: Check if database is running
docker-compose ps postgres-db postgres-warehouse

# If not running, restart
docker-compose up -d postgres-db postgres-warehouse

# Run migrations again
docker-compose exec driver-service php artisan migrate
```

### Issue 3: Driver Not Auto-Assigned
```bash
# Check if consumer is running
docker-compose logs driver-service

# Should see: "✅ Connected to RabbitMQ. Waiting for messages..."

# If not, restart service
docker-compose restart driver-service
```

### Issue 4: Hasura Tables Not Showing
1. Make sure migrations ran: `docker-compose exec warehouse-service php artisan migrate`
2. Access Hasura Console: http://localhost:8080
3. Go to DATA tab
4. Click "Track All" for untracked tables
5. Setup relationships manually (see HASURA_SETUP.md)

---

## 📊 Monitoring & Logs

### View Real-Time Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f driver-service
docker-compose logs -f order-service
docker-compose logs -f warehouse-service
docker-compose logs -f auth-service

# RabbitMQ logs
docker-compose logs -f rabbitmq
```

### Check RabbitMQ Queues
1. Open http://localhost:15672
2. Login: guest/guest
3. Check "Queues" tab for:
   - `order_created_queue`
   - `driver_assigned_queue`
   - `user_registered_queue`

### Database Inspection

#### PostgreSQL (Order & Driver)
```bash
docker-compose exec postgres-db psql -U admin -d order_tracking_db

# List tables
\dt

# Check orders
SELECT * FROM orders;

# Check driver assignments
SELECT * FROM driver_assignments;

# Exit
\q
```

#### PostgreSQL (Warehouse)
```bash
docker-compose exec postgres-warehouse psql -U warehouse_user -d warehouse_db

# List tables
\dt

# Check products
SELECT * FROM products;

# Check stocks
SELECT * FROM stocks;

# Exit
\q
```

---

## 🎯 Performance Testing

### Load Test: Multiple Orders
```bash
# Create 5 orders rapidly
for i in {1..5}; do
  curl -X POST http://localhost:4000/graphql \
    -H "Content-Type: application/json" \
    -d '{
      "query": "mutation { createOrder(customer_id: \"test@example.com\", item_description: \"Test Item '$i'\") { tracking_number } }"
    }' &
done

wait

# Check all assignments
curl http://localhost:6000/api/assignments
```

---

## 📝 Postman Collection

**Import this JSON to Postman:**

```json
{
  "info": {
    "name": "Fleet Delivery System",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Service 1 - Auth",
      "item": [
        {
          "name": "Register",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"Test User\",\n  \"email\": \"test@example.com\",\n  \"password\": \"password123\",\n  \"role\": \"sender\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "http://localhost:3000/api/auth/register",
              "protocol": "http",
              "host": ["localhost"],
              "port": "3000",
              "path": ["api", "auth", "register"]
            }
          }
        }
      ]
    }
  ]
}
```

---

## ✅ Pre-Demo Checklist

**Sebelum presentasi, pastikan:**

- [ ] Semua services running: `docker-compose ps`
- [ ] RabbitMQ accessible: http://localhost:15672
- [ ] Hasura Console accessible: http://localhost:8080
- [ ] Database migrations completed
- [ ] At least 2 drivers created
- [ ] Test 1 complete order flow
- [ ] Prepare Postman collection
- [ ] Architecture diagram ready
- [ ] Check all logs for errors

**Testing Commands Sequence:**
```bash
# 1. Health check
curl http://localhost:3000 && \
curl http://localhost:4000/graphql && \
curl http://localhost:5000/api/v1/warehouse/products && \
curl http://localhost:6000/api/drivers && \
curl http://localhost:8080/healthz

# 2. Create order and verify flow
# (Run Scenario 1 step by step)

# 3. Check logs
docker-compose logs --tail=50 driver-service
docker-compose logs --tail=50 warehouse-service
```

---

## 🎓 Tips untuk Presentasi

1. **Demo Flow Utama**: Register → Create Order → Auto Assign Driver → Track
2. **Tunjukkan RabbitMQ**: Queue messages & consumption
3. **Tunjukkan Hasura Console**: Auto-generated GraphQL
4. **Tunjukkan Different Tech Stacks**: REST vs GraphQL Manual vs Hasura
5. **Tunjukkan Database Terpisah**: Show 3 different databases
6. **Explain Message Flow**: Diagram + Real logs

Good luck! 🚀
