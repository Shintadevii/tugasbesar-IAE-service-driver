# 📬 Postman Guide - Fleet Delivery System

Panduan lengkap menggunakan Postman untuk testing semua API.

---

## 🚀 Quick Setup (3 Langkah)

### Step 1: Import Collection

1. **Buka Postman**
2. **Klik "Import"** (tombol kiri atas)
3. **Drag & Drop** file ini atau klik "Upload Files":
   ```
   Fleet-Delivery-System.postman_collection.json
   ```
4. **Klik "Import"**

✅ **Collection "Fleet Delivery System - IAE" sudah masuk!**

---

### Step 2: Import Environment

1. **Klik icon Gear ⚙️** (kanan atas, sebelah Environment dropdown)
2. **Klik "Import"**
3. **Upload file**:
   ```
   Fleet-Delivery-System.postman_environment.json
   ```
4. **Klik "Import"**
5. **Pilih environment "Fleet Delivery - Local"** di dropdown (kanan atas)

✅ **Environment variables sudah siap!**

---

### Step 3: Test Connection

1. **Expand collection** → **Service 4 - Driver Assignment**
2. **Klik "Get All Drivers"**
3. **Klik "Send"**

✅ **Kalau berhasil, kamu akan lihat response JSON!**

---

## 📋 Collection Structure

```
Fleet Delivery System - IAE
│
├── Service 1 - Auth & Customer (REST)
│   ├── Auth
│   │   ├── Register User ⭐
│   │   ├── Login ⭐
│   │   ├── Get Current User
│   │   └── Logout
│   ├── Customer
│   │   ├── Get All Customers
│   │   ├── Create Customer
│   │   └── Get Customer by ID
│   └── Agent
│       ├── Get All Agents
│       └── Create Agent
│
├── Service 2 - Order & Tracking (GraphQL)
│   ├── Create Order ⭐⭐⭐
│   ├── Track Order ⭐⭐
│   └── Update Tracking
│
├── Service 3 - Warehouse (REST)
│   ├── Get All Warehouse Orders
│   ├── Get All Products
│   ├── Get All Stocks
│   ├── Get Low Stock Items
│   ├── Update Order Status
│   └── Dispatch Order
│
├── Service 3 - Warehouse (Hasura GraphQL)
│   ├── Get All Products with Stocks
│   ├── Get Warehouse Orders
│   ├── Get Low Stock Items
│   └── Get Order by Tracking Number
│
└── Service 4 - Driver Assignment (REST)
    ├── Driver Management
    │   ├── Get All Drivers ⭐
    │   ├── Get Available Drivers
    │   ├── Create Driver ⭐
    │   ├── Get Driver by ID
    │   ├── Update Driver
    │   └── Delete Driver
    └── Assignment Management
        ├── Get All Assignments
        ├── Get Assignment by Tracking Number ⭐⭐
        ├── Get Assignments by Driver
        └── Update Assignment Status
```

**⭐ = Important for demo**

---

## 🧪 Testing Flow - Step by Step

### Flow 1: Complete Order Journey (DEMO READY!)

#### 1️⃣ Register User
```
Collection: Service 1 > Auth > Register User
Method: POST
```

**What happens:**
- Creates new user
- Returns JWT token
- **Auto-saves token** to environment variable `{{auth_token}}`

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

✅ **Success Indicator:** Status 201, token generated

---

#### 2️⃣ Create Customer Profile
```
Collection: Service 1 > Customer > Create Customer
Method: POST
```

**Note:** Token is **automatically** added from environment

**Expected Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "full_name": "Budi Santoso",
  "phone": "08123456789",
  "address": "Jl. Sudirman No. 123, Jakarta"
}
```

---

#### 3️⃣ Create Order (GraphQL) ⭐
```
Collection: Service 2 > Create Order
Method: POST (GraphQL)
```

**What happens:**
- Creates order in database
- Generates tracking number
- Publishes to RabbitMQ
- **Auto-saves tracking_number** to `{{tracking_number}}`

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
      "histories": [...]
    }
  }
}
```

✅ **IMPORTANT:** Copy the `tracking_number`!

---

#### 4️⃣ Check Driver Assignment (Wait 2 seconds)
```
Collection: Service 4 > Assignment > Get Assignment by Tracking Number
Method: GET
```

**What happens:**
- Service 4 consumer auto-assigned driver
- Check if assignment exists

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

✅ **Success Indicator:** Driver assigned automatically!

---

#### 5️⃣ Track Order
```
Collection: Service 2 > Track Order
Method: POST (GraphQL)
```

**Expected Response:**
```json
{
  "data": {
    "trackOrder": {
      "tracking_number": "REG-1718234567890-123",
      "status": "ASSIGNED_TO_DRIVER",
      "histories": [
        {
          "location": "Gudang Pengirim",
          "description": "Pesanan telah dibuat"
        },
        {
          "location": "Assigned to Driver",
          "description": "Paket telah ditugaskan ke kurir: Driver Default 42"
        }
      ]
    }
  }
}
```

✅ **Success Indicator:** History shows driver assignment!

---

#### 6️⃣ Update Tracking Status
```
Collection: Service 2 > Update Tracking
Method: POST (GraphQL)
```

**Variables to change:**
```json
{
  "location": "Jakarta Selatan",
  "description": "Paket dalam perjalanan",
  "status": "IN_TRANSIT"
}
```

---

#### 7️⃣ Check in Warehouse (Hasura)
```
Collection: Service 3 > Hasura > Get Order by Tracking Number
Method: POST (GraphQL)
```

**Note:** Uses Hasura admin secret automatically

---

### Flow 2: Driver Management

#### Create Multiple Drivers
```
Collection: Service 4 > Driver Management > Create Driver
```

**Run 3 times with different names:**
```json
{ "name": "Ahmad Kurir", "phone_number": "08111111111", "status": "available" }
{ "name": "Siti Kurir", "phone_number": "08122222222", "status": "available" }
{ "name": "Budi Kurir", "phone_number": "08133333333", "status": "available" }
```

#### Check Available Drivers
```
Collection: Service 4 > Driver Management > Get Available Drivers
```

---

## 🔧 Environment Variables

Variables yang **auto-saved** oleh requests:

| Variable | Set By | Used By |
|----------|--------|---------|
| `{{auth_token}}` | Register/Login | All authenticated endpoints |
| `{{tracking_number}}` | Create Order | Track Order, Get Assignment |
| `{{user_email}}` | Register | Create Order |

---

## 🎯 Testing Tips

### 1. Run Collection in Order
Untuk flow lengkap, run requests dalam urutan ini:
1. Register User
2. Create Customer
3. Create Driver (2-3 drivers)
4. Create Order
5. Wait 2 seconds
6. Get Assignment by Tracking
7. Track Order

### 2. Check Variables
Pastikan environment variables ter-set:
- Klik icon Eye 👁️ (kanan atas)
- Check `auth_token` dan `tracking_number` ada nilai

### 3. GraphQL Queries
Untuk GraphQL requests:
- Body type: **GraphQL**
- Tab "Query" untuk GraphQL query
- Tab "GraphQL Variables" untuk variables

### 4. Authentication
Service 1 (Auth) menggunakan Bearer token:
- Token auto-added dari `{{auth_token}}`
- Check tab "Authorization" → Type: Bearer Token

---

## 🐛 Troubleshooting

### Error: Connection Refused
```
✗ Services belum running
✓ Solution: docker-compose up -d
```

### Error: 401 Unauthorized
```
✗ Token expired atau tidak valid
✓ Solution: Login ulang untuk get new token
```

### Error: 404 Not Found
```
✗ Endpoint URL salah atau service not ready
✓ Solution: Check service logs: docker-compose logs <service-name>
```

### GraphQL Error: tracking_number required
```
✗ Variable {{tracking_number}} belum di-set
✓ Solution: Run "Create Order" first untuk auto-set variable
```

### Empty Response dari Hasura
```
✗ Tables belum di-track di Hasura
✓ Solution: 
   1. Open http://localhost:8080
   2. Login dengan admin secret: adminsecret123
   3. Track tables: products, stocks, orders
```

---

## 📊 Response Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| **200** | Success | ✅ Request berhasil |
| **201** | Created | ✅ Resource created |
| **401** | Unauthorized | ⚠️ Login ulang |
| **404** | Not Found | ⚠️ Check endpoint URL |
| **422** | Validation Error | ⚠️ Check request body |
| **500** | Server Error | ❌ Check service logs |

---

## 🎬 Demo Sequence for Presentation

**Preparation (before demo):**
1. Make sure all services running
2. Create 2-3 drivers first
3. Clear old data (optional)

**Live Demo (5 minutes):**

```
Minute 0-1: Setup
├─ Show Postman collection
└─ Show environment variables

Minute 1-2: Authentication
├─ Register User → Show token generated
└─ Create Customer Profile

Minute 2-3: Order Creation
├─ Create Order (GraphQL) → Show tracking number
├─ Open RabbitMQ UI → Show queue consumed
└─ Wait 2 seconds

Minute 3-4: Verification
├─ Get Assignment by Tracking → Show driver auto-assigned
├─ Track Order → Show history with driver info
└─ Show in Hasura Console

Minute 4-5: Additional Features
├─ Update Tracking Status
├─ Check Warehouse Orders
└─ Show all drivers and assignments
```

---

## 📝 Export Collection for Submission

1. **Klik ... (three dots)** pada collection
2. **Export**
3. **Choose "Collection v2.1"**
4. **Save file**
5. **Share link** atau upload ke GitHub

---

## 🔗 Useful URLs

- **RabbitMQ Management**: http://localhost:15672 (guest/guest)
- **Hasura Console**: http://localhost:8080 (admin: adminsecret123)
- **GraphQL Playground (Order)**: http://localhost:4000/graphql

---

## ✅ Pre-Demo Checklist

Sebelum presentasi, test semua endpoint ini:

- [ ] Service 1: Register → Returns token
- [ ] Service 1: Create Customer → Success
- [ ] Service 4: Create Driver → Success
- [ ] Service 4: Get All Drivers → Shows drivers
- [ ] Service 2: Create Order → Returns tracking_number
- [ ] Wait 5 seconds
- [ ] Service 4: Get Assignment → Shows driver assigned
- [ ] Service 2: Track Order → Shows history
- [ ] Service 3: Get Warehouse Orders (REST) → Shows orders
- [ ] Service 3: Get Orders (Hasura) → Shows orders

**All green? Ready for demo! 🚀**

---

## 🎓 Notes for Documentation

**Add this to your documentation PDF:**

> "API testing menggunakan Postman Collection yang berisi 40+ requests untuk 4 microservices. Collection mendukung:
> - Automatic token management
> - Environment variables untuk tracking_number
> - Pre-request scripts untuk authentication
> - GraphQL queries dengan variables
> - Complete flow testing dari registration hingga delivery tracking"

**Postman Collection Features:**
- ✅ 40+ API endpoints
- ✅ REST + GraphQL (Manual) + Hasura GraphQL
- ✅ Auto token management
- ✅ Environment variables
- ✅ Request examples with expected responses
- ✅ Organized by service

---

**Happy Testing! 📬**
