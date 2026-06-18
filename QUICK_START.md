# ⚡ Quick Start Guide - Fleet Delivery System

**Panduan cepat untuk menjalankan project dalam 5 menit!**

---

## 🚀 Setup & Run (First Time)

### 1. Clone & Navigate
```bash
cd "c:\kuliah\semester 4\TUBES_EAI"
```

### 2. Copy Environment Files
```bash
# Windows CMD
copy tugasbesar-IAE\.env.example tugasbesar-IAE\.env
copy Logistik-order-service\.env.example Logistik-order-service\.env
copy warehouse-api\.env.example warehouse-api\.env
copy tugasbesar-IAE-service-driver\.env.example tugasbesar-IAE-service-driver\.env
```

### 3. Start All Services
```bash
docker-compose up -d --build
```

**⏱️ Wait 30-60 seconds for all services to start...**

### 4. Run Database Migrations
```bash
# Service 1 - Auth (Laravel)
docker-compose exec auth-service php artisan key:generate
docker-compose exec auth-service php artisan migrate --seed

# Service 2 - Order (Node.js + Prisma)
docker-compose exec order-service npx prisma migrate deploy

# Service 3 - Warehouse (Laravel)
docker-compose exec warehouse-service php artisan key:generate
docker-compose exec warehouse-service php artisan migrate --seed

# Service 4 - Driver (Laravel)
docker-compose exec driver-service php artisan key:generate
docker-compose exec driver-service php artisan migrate --seed
```

### 5. Setup Hasura (One-Time)
1. Open browser: http://localhost:8080
2. Enter admin secret: `adminsecret123`
3. Click "DATA" tab
4. Click "Track All" for tables: `products`, `stocks`, `orders`
5. Setup relationships (optional, see HASURA_SETUP.md)

### 6. Create Test Data
```bash
# Create drivers
docker-compose exec driver-service php artisan tinker
```

In tinker console:
```php
App\Models\Driver::create(['name' => 'Ahmad Kurir', 'phone_number' => '08111111111', 'status' => 'available']);
App\Models\Driver::create(['name' => 'Siti Kurir', 'phone_number' => '08122222222', 'status' => 'available']);
exit
```

---

## ✅ Verify Installation

```bash
# Check all containers running
docker-compose ps

# Should see 8 containers running:
# - auth-service
# - order-service
# - warehouse-service
# - driver-service
# - rabbitmq
# - postgres-db
# - postgres-warehouse
# - hasura-warehouse
```

### Test Each Service

#### Service 1 - Auth (Port 3000)
```bash
curl -X POST http://localhost:3000/api/auth/register -H "Content-Type: application/json" -d "{\"name\":\"Test User\",\"email\":\"test@example.com\",\"password\":\"password123\",\"role\":\"sender\"}"
```

#### Service 2 - Order GraphQL (Port 4000)
```bash
curl -X POST http://localhost:4000/graphql -H "Content-Type: application/json" -d "{\"query\":\"mutation { createOrder(customer_id: \\\"test@example.com\\\", item_description: \\\"Test Item\\\") { tracking_number status } }\"}"
```

#### Service 3 - Warehouse (Port 5000 & 8080)
```bash
curl http://localhost:5000/api/v1/warehouse/products
curl http://localhost:8080/healthz
```

#### Service 4 - Driver (Port 6000)
```bash
curl http://localhost:6000/api/drivers
```

#### RabbitMQ Management (Port 15672)
Open browser: http://localhost:15672
- Username: `guest`
- Password: `guest`

---

## 🧪 Test Complete Flow (DEMO READY!)

### Step 1: Register Customer
```bash
curl -X POST http://localhost:3000/api/auth/register -H "Content-Type: application/json" -d "{\"name\":\"Budi Santoso\",\"email\":\"budi@example.com\",\"password\":\"password123\",\"role\":\"sender\"}"
```

**Save the token from response!**

### Step 2: Create Order
```bash
curl -X POST http://localhost:4000/graphql -H "Content-Type: application/json" -d "{\"query\":\"mutation { createOrder(customer_id: \\\"budi@example.com\\\", item_description: \\\"Laptop Gaming\\\") { id tracking_number status histories { location description } } }\"}"
```

**Copy the tracking_number from response!**

### Step 3: Check Driver Assignment (Auto!)
```bash
# Replace TRACKING_NUMBER with actual value
curl http://localhost:6000/api/assignments/tracking/REG-XXXXXXXXXX
```

### Step 4: Track Order
```bash
# Replace TRACKING_NUMBER with actual value
curl -X POST http://localhost:4000/graphql -H "Content-Type: application/json" -d "{\"query\":\"query { trackOrder(tracking_number: \\\"REG-XXXXXXXXXX\\\") { tracking_number status histories { location description updated_at } } }\"}"
```

---

## 🎯 Important URLs

| Service | URL | Purpose |
|---------|-----|---------|
| **Auth API** | http://localhost:3000/api | REST endpoints for auth |
| **Order GraphQL** | http://localhost:4000/graphql | GraphQL playground |
| **Warehouse REST** | http://localhost:5000/api/v1 | REST endpoints |
| **Hasura Console** | http://localhost:8080 | GraphQL console (admin: adminsecret123) |
| **Driver API** | http://localhost:6000/api | REST endpoints |
| **RabbitMQ UI** | http://localhost:15672 | Queue management (guest/guest) |

---

## 🛑 Stop & Clean

### Stop Services
```bash
docker-compose down
```

### Complete Clean (Remove data)
```bash
docker-compose down -v
```

### Restart Services
```bash
docker-compose restart
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f driver-service
```

---

## 📋 Pre-Demo Checklist

**Before presentation, verify:**

- [ ] All 8 containers running: `docker-compose ps`
- [ ] RabbitMQ accessible: http://localhost:15672
- [ ] Hasura console accessible: http://localhost:8080
- [ ] Test order flow works end-to-end
- [ ] At least 2 drivers exist: `curl http://localhost:6000/api/drivers`
- [ ] Postman collection imported
- [ ] Architecture diagram ready
- [ ] No error in logs: `docker-compose logs --tail=50`

---

## 🐛 Common Issues

### Issue: Port already in use
```bash
# Find process using port 3000 (example)
netstat -ano | findstr :3000

# Kill process by PID
taskkill /PID <PID> /F
```

### Issue: Database connection error
```bash
# Restart databases
docker-compose restart postgres-db postgres-warehouse

# Wait 10 seconds, then restart services
docker-compose restart auth-service order-service warehouse-service driver-service
```

### Issue: RabbitMQ not working
```bash
# Restart RabbitMQ
docker-compose restart rabbitmq

# Wait 10 seconds, then restart consumers
docker-compose restart driver-service warehouse-service
```

### Issue: Migrations failed
```bash
# Drop all volumes and start fresh
docker-compose down -v
docker-compose up -d --build
# Run migrations again (step 4)
```

---

## 📚 Next Steps

1. ✅ System running? → Read [TESTING_GUIDE.md](./TESTING_GUIDE.md)
2. ✅ Ready to demo? → Read [README.md](./README.md)
3. ✅ Need architecture details? → Read [ARCHITECTURE.md](./ARCHITECTURE.md)
4. ✅ Hasura setup? → Read [warehouse-api/HASURA_SETUP.md](./warehouse-api/HASURA_SETUP.md)

---

## 🎓 For Presentation

### Demo Flow (5 minutes):
1. Show architecture diagram
2. Start all services: `docker-compose ps`
3. Open RabbitMQ UI: http://localhost:15672
4. Create order via Postman/cURL
5. Show queue message consumed
6. Show driver auto-assigned
7. Track order with updated history
8. Show Hasura GraphQL console
9. Explain tech stack differences

### Key Points to Highlight:
- ✅ 4 microservices with separate databases
- ✅ RESTful API (Service 1, 4)
- ✅ GraphQL Manual (Service 2)
- ✅ GraphQL Hasura (Service 3)
- ✅ RabbitMQ message broker
- ✅ Docker containerization
- ✅ Event-driven architecture

**Good luck! 🚀**
