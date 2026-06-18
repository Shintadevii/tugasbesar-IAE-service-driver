# 🏗️ Architecture Documentation - Fleet Delivery System

## 📊 System Architecture Overview

```
┌────────────────────────────────────────────────────────────────────────┐
│                          CLIENT / API TESTING TOOLS                     │
│                    (Postman, cURL, GraphQL Playground)                  │
└────────────────────────────────────────────────────────────────────────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            │                       │                       │
            ▼                       ▼                       ▼
    ┌───────────────┐       ┌───────────────┐     ┌───────────────┐
    │  SERVICE 1    │       │  SERVICE 2    │     │  SERVICE 3    │
    │               │       │               │     │               │
    │ Auth & User   │       │Order&Tracking │     │  Warehouse    │
    │  Management   │       │               │     │  Management   │
    │               │       │               │     │               │
    │ ┌───────────┐ │       │ ┌───────────┐ │     │ ┌───────────┐ │
    │ │  REST API │ │       │ │  GraphQL  │ │     │ │   Hasura  │ │
    │ │  Laravel  │ │       │ │  Manual   │ │     │ │  GraphQL  │ │
    │ └───────────┘ │       │ │  Apollo   │ │     │ └───────────┘ │
    │               │       │ │  Node.js  │ │     │               │
    │ Port: 3000    │       │ └───────────┘ │     │ Port: 8080    │
    │               │       │               │     │ (+ REST 5000) │
    │ MySQL         │       │ PostgreSQL    │     │ PostgreSQL    │
    └───────┬───────┘       └───────┬───────┘     └───────┬───────┘
            │                       │                     │
            │ Publish               │ Publish             │ Consume
            │ Events                │ Events              │ Events
            │                       │                     │
            └───────────────┐       │       ┐─────────────┘
                            │       │       │
                            ▼       ▼       ▼
                    ┌─────────────────────────────┐
                    │   🐰 RABBITMQ (Broker)      │
                    │                             │
                    │  Queues:                    │
                    │  • order_created_queue      │
                    │  • driver_assigned_queue    │
                    │  • user_registered_queue    │
                    │  • customer_created_queue   │
                    │  • agent_created_queue      │
                    │                             │
                    │  Port: 5672 (AMQP)          │
                    │  Port: 15672 (Management)   │
                    └──────────────┬──────────────┘
                                   │
                                   │ Consume Events
                                   │
                                   ▼
                           ┌───────────────┐
                           │  SERVICE 4    │
                           │               │
                           │    Driver     │
                           │  Assignment   │
                           │               │
                           │ ┌───────────┐ │
                           │ │ RabbitMQ  │ │
                           │ │ Consumer  │ │
                           │ │  Laravel  │ │
                           │ └───────────┘ │
                           │               │
                           │ Port: 6000    │
                           │ PostgreSQL    │
                           └───────────────┘
```

---

## 🔄 Data Flow Diagrams

### Flow 1: Order Creation & Auto Assignment

```
┌─────────────┐
│   CLIENT    │
└──────┬──────┘
       │ 1. POST /api/auth/register
       ▼
┌─────────────────────────┐
│   SERVICE 1 (Auth)      │
│   - Create user         │
│   - Generate token      │───┐
└─────────────────────────┘   │ 2. Publish to RabbitMQ
                               │    (user_registered_queue)
                               ▼
                        ┌──────────────┐
┌────────────┐          │  RABBITMQ    │
│   CLIENT   │          └──────────────┘
└──────┬─────┘
       │ 3. GraphQL Mutation: createOrder
       ▼
┌─────────────────────────────────────┐
│   SERVICE 2 (Order)                 │
│   - Create order in PostgreSQL      │
│   - Generate tracking_number        │
│   - Create tracking history         │
└──────────────┬──────────────────────┘
               │ 4. Publish to RabbitMQ
               │    (order_created_queue)
               ▼
        ┌──────────────┐
        │  RABBITMQ    │
        │              │
        └──┬────────┬──┘
           │        │
    5a.    │        │    5b.
   Consume │        │ Consume
           ▼        ▼
    ┌──────────┐  ┌──────────────┐
    │SERVICE 3 │  │  SERVICE 4   │
    │Warehouse │  │    Driver    │
    │          │  │  Assignment  │
    │- Record  │  │              │
    │  in DB   │  │- Find driver │
    │- Update  │  │- Create      │
    │  stock   │  │  assignment  │
    └──────────┘  │- Update      │
                  │  driver      │
                  │  status      │
                  └──────┬───────┘
                         │ 6. Update tracking
                         │    via GraphQL
                         ▼
                  ┌──────────────┐
                  │  SERVICE 2   │
                  │   (Order)    │
                  │- Add history │
                  └──────────────┘
```

### Flow 2: Order Tracking

```
┌─────────────┐
│   CLIENT    │
└──────┬──────┘
       │ GraphQL Query: trackOrder
       ▼
┌────────────────────────────────┐
│   SERVICE 2 (Order)            │
│   - Query by tracking_number   │
│   - Include all histories      │
│   - Return order details       │
└────────────────────────────────┘
       │
       ▼
┌─────────────┐
│   CLIENT    │
│  (Response) │
│             │
│ - Order ID  │
│ - Status    │
│ - History:  │
│   • Created │
│   • Assigned│
│   • Transit │
└─────────────┘
```

### Flow 3: Warehouse Operations via Hasura

```
┌─────────────┐
│   CLIENT    │
└──────┬──────┘
       │ GraphQL Query via Hasura
       ▼
┌────────────────────────────────┐
│   HASURA (Service 3)           │
│   - Auto-generate query        │
│   - Apply filters              │
│   - Join with relationships    │
└────────────────┬───────────────┘
                 │
                 ▼
        ┌────────────────┐
        │  PostgreSQL    │
        │  (Warehouse)   │
        │                │
        │ - products     │
        │ - stocks       │
        │ - orders       │
        └────────────────┘
```

---

## 🗄️ Database Architecture

### Database 1: MySQL (Service 1 - Auth)
```
┌─────────────────────────────┐
│          USERS              │
├─────────────────────────────┤
│ id (PK)                     │
│ name                        │
│ email (UNIQUE)              │
│ password (HASHED)           │
│ role (sender/agent/admin)   │
│ created_at                  │
│ updated_at                  │
└────────┬────────────────────┘
         │ 1:N
         ├─────────────────────┐
         │                     │
         ▼                     ▼
┌────────────────┐    ┌────────────────┐
│   CUSTOMERS    │    │    AGENTS      │
├────────────────┤    ├────────────────┤
│ id (PK)        │    │ id (PK)        │
│ user_id (FK)   │    │ user_id (FK)   │
│ full_name      │    │ full_name      │
│ phone          │    │ phone          │
│ address        │    │ branch_name    │
│ created_at     │    │ address        │
│ updated_at     │    │ created_at     │
└────────────────┘    │ updated_at     │
                      └────────────────┘
```

### Database 2: PostgreSQL (Service 2 - Order & Service 4 - Driver)
```
┌──────────────────────────────┐
│          ORDERS              │
├──────────────────────────────┤
│ id (PK)                      │
│ tracking_number (UNIQUE)     │
│ customer_id                  │
│ item_description             │
│ status                       │
│ created_at                   │
│ updated_at                   │
└────────┬─────────────────────┘
         │ 1:N
         ▼
┌──────────────────────────────┐
│    TRACKING_HISTORIES        │
├──────────────────────────────┤
│ id (PK)                      │
│ order_id (FK)                │
│ location                     │
│ description                  │
│ created_at                   │
│ updated_at                   │
└──────────────────────────────┘

┌──────────────────────────────┐         ┌──────────────────────────────┐
│          DRIVERS             │         │    DRIVER_ASSIGNMENTS        │
├──────────────────────────────┤         ├──────────────────────────────┤
│ id (PK)                      │ 1:N     │ id (PK)                      │
│ name                         │◄────────┤ driver_id (FK)               │
│ phone_number                 │         │ order_id (tracking_number)   │
│ status (available/busy)      │         │ status                       │
│ created_at                   │         │ created_at                   │
│ updated_at                   │         │ updated_at                   │
└──────────────────────────────┘         └──────────────────────────────┘
```

### Database 3: PostgreSQL (Service 3 - Warehouse)
```
┌──────────────────────────────┐
│          PRODUCTS            │
├──────────────────────────────┤
│ id (PK)                      │
│ sku (UNIQUE)                 │
│ name                         │
│ description                  │
│ price                        │
│ category                     │
│ created_at                   │
│ updated_at                   │
└────────┬─────────────────────┘
         │ 1:N
         ├─────────────────────┐
         │                     │
         ▼                     ▼
┌────────────────┐    ┌────────────────┐
│    STOCKS      │    │    ORDERS      │
├────────────────┤    ├────────────────┤
│ id (PK)        │    │ id (PK)        │
│ product_id(FK) │    │ reference      │
│ location       │    │ status         │
│ quantity       │    │ product_id(FK) │
│ reorder_level  │    │ quantity       │
│ created_at     │    │ total          │
│ updated_at     │    │ created_at     │
└────────────────┘    │ updated_at     │
                      └────────────────┘
```

---

## 🔌 API Communication Matrix

| Service | Exposes | Consumes | Protocol |
|---------|---------|----------|----------|
| **Service 1 (Auth)** | REST API | - | HTTP/REST |
| **Service 2 (Order)** | GraphQL API | - | HTTP/GraphQL |
| **Service 3 (Warehouse)** | Hasura GraphQL + REST | RabbitMQ: `order_created_queue` | HTTP/GraphQL + AMQP |
| **Service 4 (Driver)** | REST API | RabbitMQ: `order_created_queue` | HTTP/REST + AMQP |

### Inter-Service Communication

```
Service 1 ──(RabbitMQ)──> Service 4 (future: notification)
Service 2 ──(RabbitMQ)──> Service 3 (warehouse)
Service 2 ──(RabbitMQ)──> Service 4 (driver assignment)
Service 4 ──(HTTP/GraphQL)──> Service 2 (update tracking)
```

---

## 🔐 Security & Authentication

### Service 1: Laravel Sanctum
```
┌─────────────┐
│   Client    │
│             │
└──────┬──────┘
       │ POST /api/auth/login
       ▼
┌─────────────────────────┐
│   Service 1             │
│   - Verify credentials  │
│   - Generate token      │
└──────┬──────────────────┘
       │ Return token
       ▼
┌─────────────┐
│   Client    │
│  Store JWT  │
└──────┬──────┘
       │ Authorization: Bearer {token}
       ▼
┌─────────────────────────┐
│   Protected Routes      │
│   - Verify token        │
│   - Allow access        │
└─────────────────────────┘
```

### Service 2 & 3: Public for Demo
- No authentication required for demo purposes
- In production: Implement JWT validation

### Service 4: Public REST API
- Driver management open for demo
- In production: Implement role-based access control

---

## 🚀 Deployment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Docker Host Machine                      │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Docker Compose Network                   │   │
│  │              (logistik-network)                       │   │
│  │                                                        │   │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐    │   │
│  │  │Service 1│ │Service 2│ │Service 3│ │Service 4│    │   │
│  │  │ (3000)  │ │ (4000)  │ │(5000/   │ │ (6000)  │    │   │
│  │  │         │ │         │ │ 8080)   │ │         │    │   │
│  │  └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘    │   │
│  │       │           │           │           │         │   │
│  │       └───────────┴───────────┴───────────┘         │   │
│  │                       │                              │   │
│  │                       ▼                              │   │
│  │              ┌─────────────────┐                     │   │
│  │              │   RabbitMQ      │                     │   │
│  │              │  (5672/15672)   │                     │   │
│  │              └─────────────────┘                     │   │
│  │                                                        │   │
│  │  ┌─────────┐ ┌─────────────┐ ┌─────────────┐        │   │
│  │  │ MySQL   │ │ Postgres    │ │ Postgres    │        │   │
│  │  │ (Auth)  │ │ (Order)     │ │ (Warehouse) │        │   │
│  │  │ (3306)  │ │ (5433)      │ │ (5434)      │        │   │
│  │  └─────────┘ └─────────────┘ └─────────────┘        │   │
│  │                                                        │   │
│  └────────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘

Exposed Ports:
• 3000 → Auth Service
• 4000 → Order Service (GraphQL)
• 5000 → Warehouse REST API
• 6000 → Driver Service
• 8080 → Hasura GraphQL Console
• 15672 → RabbitMQ Management UI
```

---

## 📈 Scalability Considerations

### Horizontal Scaling Potential

```
         Load Balancer
              │
    ┌─────────┼─────────┐
    ▼         ▼         ▼
┌─────────┐ ┌─────────┐ ┌─────────┐
│Service 1│ │Service 1│ │Service 1│
│Instance │ │Instance │ │Instance │
│   #1    │ │   #2    │ │   #3    │
└─────────┘ └─────────┘ └─────────┘
```

**Services yang bisa di-scale:**
- ✅ Service 1 (Stateless REST API)
- ✅ Service 2 (Stateless GraphQL)
- ✅ Service 4 (Consumer - multiple workers)
- ⚠️ Service 3 (Hasura - requires load balancing config)

---

## 🔧 Technology Stack Details

### Service 1: Auth & Customer Service
```
┌─────────────────────────────────┐
│        Application Layer         │
│  Laravel 13 Framework            │
│  • Routing                       │
│  • Middleware (auth:sanctum)     │
│  • Controllers (REST)            │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│        Business Logic            │
│  • User Authentication           │
│  • Customer Management           │
│  • Agent Management              │
│  • Event Publishing (RabbitMQ)   │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│        Data Layer                │
│  • Eloquent ORM                  │
│  • MySQL Database                │
│  • php-amqplib (RabbitMQ)        │
└──────────────────────────────────┘
```

### Service 2: Order & Tracking Service
```
┌─────────────────────────────────┐
│        GraphQL Layer             │
│  Apollo Server 4                 │
│  • Type Definitions (typeDefs)   │
│  • Resolvers                     │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│        Business Logic            │
│  • Order Creation                │
│  • Tracking Management           │
│  • Event Publishing (RabbitMQ)   │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│        Data Layer                │
│  • Prisma ORM                    │
│  • PostgreSQL Database           │
│  • amqplib (RabbitMQ)            │
└──────────────────────────────────┘
```

### Service 3: Warehouse Service
```
┌─────────────────────────────────┐
│     GraphQL Layer (Hasura)       │
│  Hasura GraphQL Engine           │
│  • Auto-generated Schema         │
│  • Introspection                 │
│  • Real-time Subscriptions       │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│     Application Layer (Laravel)  │
│  • REST API Endpoints            │
│  • RabbitMQ Consumer             │
│  • Business Logic                │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│        Data Layer                │
│  • Eloquent ORM / Hasura Direct  │
│  • PostgreSQL Database           │
│  • php-amqplib (RabbitMQ)        │
└──────────────────────────────────┘
```

### Service 4: Driver Assignment Service
```
┌─────────────────────────────────┐
│        API Layer                 │
│  Laravel 13 Framework            │
│  • REST Endpoints                │
│  • Driver Management             │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│     Consumer Layer               │
│  Artisan Command (Console)       │
│  • Listen to RabbitMQ            │
│  • Auto Driver Assignment        │
│  • Publish Events                │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│     Integration Layer            │
│  • GraphQL Client (Order Update) │
│  • RabbitMQ Publisher            │
└────────────┬────────────────────┘
             │
┌────────────▼────────────────────┐
│        Data Layer                │
│  • Eloquent ORM                  │
│  • PostgreSQL Database           │
│  • php-amqplib (RabbitMQ)        │
└──────────────────────────────────┘
```

---

## 🎯 Design Principles Applied

### 1. Microservices Architecture
✅ **Loose Coupling**: Services communicate via events (RabbitMQ)
✅ **High Cohesion**: Each service has single responsibility
✅ **Independent Deployment**: Each service has own container
✅ **Technology Diversity**: Different stacks per service

### 2. Event-Driven Architecture
✅ **Asynchronous Communication**: RabbitMQ message broker
✅ **Event Publishing**: Services publish domain events
✅ **Event Consumption**: Background consumers process events

### 3. API Design Patterns
✅ **REST API**: Service 1, 4 (Resource-oriented)
✅ **GraphQL**: Service 2 (Query flexibility)
✅ **Auto-Generated GraphQL**: Service 3 (Hasura - Zero coding)

### 4. Database Per Service
✅ **Service 1**: MySQL (Auth domain)
✅ **Service 2**: PostgreSQL (Order domain)
✅ **Service 3**: PostgreSQL (Warehouse domain)
✅ **Service 4**: PostgreSQL (Shared with Order for simplicity)

---

## 📊 Monitoring & Observability

### Logs
- **Application Logs**: Each service writes to stdout
- **Docker Logs**: `docker-compose logs <service>`
- **RabbitMQ Logs**: Available in management UI

### Metrics
- **RabbitMQ Management UI**: Queue depths, message rates
- **Database Connections**: Monitored via container stats
- **API Response Times**: Can be measured via Postman

### Alerts (Future Enhancement)
- Queue depth exceeds threshold
- No available drivers
- Low stock alerts
- Failed message processing

---

This architecture ensures:
- ✅ **Scalability**: Services can scale independently
- ✅ **Maintainability**: Clear separation of concerns
- ✅ **Reliability**: Message broker ensures delivery
- ✅ **Flexibility**: Multiple API protocols supported
- ✅ **Compliance**: Meets all course requirements
