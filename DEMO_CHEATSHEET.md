# 🎯 DEMO CHEATSHEET - Quick Reference

**Print atau buka di layar kedua saat presentasi!**

---

## 📋 Pre-Demo Checklist (5 minutes before)

```bash
# 1. Start all services
cd "c:\kuliah\semester 4\TUBES_EAI"
docker-compose up -d

# 2. Check all running
docker-compose ps
# Expected: 8 containers running ✓

# 3. Create test drivers
docker-compose exec driver-service php artisan tinker
```

In tinker:
```php
App\Models\Driver::create(['name' => 'Ahmad', 'phone_number' => '0811111', 'status' => 'available']);
App\Models\Driver::create(['name' => 'Siti', 'phone_number' => '0822222', 'status' => 'available']);
exit
```

**Open in Browser:**
- [ ] RabbitMQ: http://localhost:15672 (guest/guest)
- [ ] Hasura: http://localhost:8080 (adminsecret123)
- [ ] Postman: Ready with collection imported

---

## 🚀 DEMO FLOW (7 minutes)

### Minute 0-1: Introduction
**"Kami akan demo Fleet Delivery System dengan 4 microservices"**

**Show diagram:** (Open ARCHITECTURE.md or slide)
- Service 1: Auth (REST) - Port 3000
- Service 2: Order (GraphQL Manual) - Port 4000
- Service 3: Warehouse (Hasura) - Port 8080
- Service 4: Driver (Consumer) - Port 6000

---

### Minute 1-2: Show Architecture

**"Lihat docker-compose.yml dan semua service running"**

```bash
docker-compose ps
```

**Point out:**
- ✅ 4 application services
- ✅ 3 separate databases
- ✅ RabbitMQ message broker
- ✅ Hasura GraphQL Engine

---

### Minute 2-3: Demo Service 1 (REST API)

**Open Postman → Service 1**

**1. Register User**
```
POST http://localhost:3000/api/auth/register
```
**Show response:**
- ✅ Token generated
- ✅ Auto-saved to environment

**2. Create Customer**
```
POST http://localhost:3000/api/customers
Headers: Authorization: Bearer {{auth_token}}
```
**Show response:**
- ✅ Customer created
- ✅ Token automatically added

---

### Minute 3-4: Demo Service 2 (GraphQL Manual)

**Open Postman → Service 2**

**Create Order (GraphQL Mutation)**
```graphql
mutation {
  createOrder(
    customer_id: "budi@example.com"
    item_description: "Laptop Gaming ASUS ROG"
  ) {
    tracking_number
    status
    histories {
      location
      description
    }
  }
}
```

**Show response:**
- ✅ Tracking number generated: `REG-XXXXXXXXXX`
- ✅ Auto-saved to `{{tracking_number}}`

**Important: COPY TRACKING NUMBER!**

---

### Minute 4-5: Show Message Broker

**Open Browser → RabbitMQ Management**
```
http://localhost:15672
Login: guest/guest
```

**Navigate to "Queues" tab**

**Point out:**
- ✅ `order_created_queue` - Messages delivered
- ✅ Consumers: 2 (Warehouse + Driver)
- ✅ Messages consumed automatically

**"Ini adalah event-driven architecture menggunakan RabbitMQ"**

---

### Minute 5-6: Demo Service 4 (Auto Driver Assignment)

**Back to Postman → Service 4**

**Get Assignment by Tracking Number**
```
GET http://localhost:6000/api/assignments/tracking/{{tracking_number}}
```

**Show response:**
```json
{
  "success": true,
  "data": {
    "order_id": "REG-XXXXXXXXXX",
    "driver": {
      "name": "Ahmad",
      "phone_number": "0811111",
      "status": "busy"
    },
    "status": "assigned"
  }
}
```

**Point out:**
- ✅ Driver **automatically** assigned
- ✅ No manual intervention
- ✅ Consumer listened to RabbitMQ

---

### Minute 6-7: Demo Service 2 (Track Order)

**Postman → Service 2 → Track Order**
```graphql
query {
  trackOrder(tracking_number: "{{tracking_number}}") {
    status
    histories {
      location
      description
      updated_at
    }
  }
}
```

**Show response:**
```json
{
  "data": {
    "trackOrder": {
      "status": "ASSIGNED_TO_DRIVER",
      "histories": [
        {
          "location": "Gudang Pengirim",
          "description": "Pesanan telah dibuat"
        },
        {
          "location": "Assigned to Driver",
          "description": "Paket telah ditugaskan ke kurir: Ahmad"
        }
      ]
    }
  }
}
```

**Point out:**
- ✅ Complete tracking history
- ✅ Driver info included
- ✅ Real-time updates

---

### Minute 7-8: Demo Service 3 (Hasura GraphQL)

**Open Browser → Hasura Console**
```
http://localhost:8080
Admin Secret: adminsecret123
```

**In GraphiQL interface, run query:**
```graphql
query {
  orders(where: {reference: {_eq: "REG-XXXXXXXXXX"}}) {
    reference
    status
    product {
      name
    }
  }
}
```

**Point out:**
- ✅ Auto-generated GraphQL (no manual coding!)
- ✅ Warehouse received order from queue
- ✅ Relationships already configured

**Show REST API too:**
```
GET http://localhost:5000/api/v1/warehouse/orders
```

---

## 💡 KEY POINTS TO EMPHASIZE

### 1. **Microservices Architecture**
✅ "4 service terpisah dengan responsibility berbeda"
✅ "Bisa di-deploy dan di-scale independently"

### 2. **Technology Diversity**
✅ "Service 1: Laravel REST API"
✅ "Service 2: Node.js + Apollo GraphQL (MANUAL)"
✅ "Service 3: Laravel + Hasura (AUTO-GENERATED)"
✅ "Service 4: Laravel + RabbitMQ Consumer"

### 3. **Database Separation**
✅ "3 database terpisah sesuai domain"
✅ "MySQL untuk Auth"
✅ "PostgreSQL untuk Order/Driver"
✅ "PostgreSQL terpisah untuk Warehouse"

### 4. **Event-Driven Architecture**
✅ "RabbitMQ sebagai message broker"
✅ "Asynchronous communication"
✅ "Loose coupling antar services"

### 5. **Docker Containerization**
✅ "Semua service dalam container"
✅ "docker-compose untuk orchestration"
✅ "Easy deployment dan scaling"

---

## 🎤 TALKING POINTS

**Introduction:**
> "Fleet Delivery System adalah aplikasi logistik berbasis microservices dengan 4 service yang terintegrasi menggunakan REST API, GraphQL manual, Hasura GraphQL, dan RabbitMQ message broker."

**During Demo:**
> "Perhatikan bahwa ketika order dibuat, secara otomatis RabbitMQ akan mengirim event ke Warehouse dan Driver service. Driver service kemudian akan assign kurir yang available tanpa intervensi manual."

**Technical Highlights:**
> "Kami mengimplementasikan 3 jenis API:
> 1. RESTful untuk Auth & Driver - traditional tapi proven
> 2. GraphQL manual untuk Order - flexible queries
> 3. Hasura GraphQL untuk Warehouse - zero-code GraphQL engine"

**Conclusion:**
> "Sistem ini memenuhi semua requirement:
> - ✅ Docker containerization
> - ✅ RESTful API
> - ✅ GraphQL manual dan Hasura
> - ✅ RabbitMQ message broker
> - ✅ Microservices architecture
> - ✅ Database terpisah per service"

---

## 🐛 IF SOMETHING GOES WRONG

### Demo fails? Have backup!

**Plan B: Screenshots**
- Prepare screenshots of successful responses
- Show from folder: `/screenshots`

**Plan C: Video**
- Record complete flow beforehand
- Play video if live demo fails

**Plan D: Logs**
```bash
# Show that consumer is working
docker-compose logs driver-service --tail=20

# Show RabbitMQ management UI
# Show database records
docker-compose exec postgres-db psql -U admin -d order_tracking_db -c "SELECT * FROM orders LIMIT 5;"
```

---

## 📊 EXPECTED DEMO RESULTS

**Service Health Check:**
```
✅ auth-service: Running (port 3000)
✅ order-service: Running (port 4000)
✅ warehouse-service: Running (port 5000)
✅ driver-service: Running (port 6000)
✅ hasura-warehouse: Running (port 8080)
✅ rabbitmq: Running (ports 5672, 15672)
✅ postgres-db: Running
✅ postgres-warehouse: Running
```

**Flow Results:**
```
1. Register → Token: "1|abcd..." ✅
2. Create Order → Tracking: "REG-171..." ✅
3. RabbitMQ → Message consumed ✅
4. Driver Assigned → Automatically ✅
5. Track Order → History updated ✅
6. Hasura → Order visible ✅
```

---

## 🎯 Q&A PREPARATION

**Q: "Kenapa database untuk Order dan Driver digabung?"**
A: "Untuk demo efficiency. In production, bisa dipisah sepenuhnya. Architecture-nya sudah support separation."

**Q: "Bagaimana kalau RabbitMQ down?"**
A: "Messages akan di-queue sampai service available lagi. RabbitMQ supports persistence and clustering untuk high availability."

**Q: "Scalability-nya gimana?"**
A: "Setiap service bisa di-scale horizontal. Tambah instances, load balancer di depan. RabbitMQ juga support clustering."

**Q: "Security-nya?"**
A: "Service 1 pakai Laravel Sanctum (JWT). In production, bisa tambah API Gateway untuk centralized auth, rate limiting, dan monitoring."

**Q: "Monitoring?"**
A: "Bisa integrate dengan Prometheus + Grafana untuk metrics, ELK stack untuk logging, dan Jaeger untuk distributed tracing."

---

## ✅ POST-DEMO CHECKLIST

After demo:
- [ ] Show docker-compose.yml structure
- [ ] Show project folder structure
- [ ] Mention documentation files (README, ARCHITECTURE)
- [ ] Show Postman collection (40+ endpoints)
- [ ] Thank the audience

---

## 🎓 FINAL NOTES

**Time Management:**
- Total: 10 minutes (including Q&A)
- Demo: 7 minutes
- Q&A: 3 minutes

**Backup Materials:**
- Screenshots folder ready
- Video recording ready
- Printed architecture diagram
- This cheatsheet (printed or on second screen)

**Confidence Boosters:**
- ✅ You tested the complete flow
- ✅ All services are working
- ✅ Documentation is complete
- ✅ Postman collection is ready
- ✅ You understand the architecture

---

**YOU GOT THIS! 🚀**

**Remember:**
- Speak clearly and confidently
- Show enthusiasm about the tech
- Explain the "why" not just the "what"
- Make eye contact
- Smile!

**Good luck! 🎉**
