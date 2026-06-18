# 📦 Service 2: Order & Tracking Service

**Technology Stack:** Node.js + Apollo Server + GraphQL + Prisma + PostgreSQL + RabbitMQ

---

## 📋 Description

Service untuk manajemen order dan tracking paket. Menggunakan **GraphQL manual implementation** dengan Apollo Server.

## 🎯 Features

- ✅ Create Order (GraphQL Mutation)
- ✅ Track Order by Tracking Number (GraphQL Query)
- ✅ Update Tracking History (GraphQL Mutation)
- ✅ RabbitMQ Event Publishing
- ✅ Real-time Tracking History

## 🔌 GraphQL API

**Endpoint:** `http://localhost:4000/graphql`

### Queries

```graphql
query TrackOrder($trackingNumber: String!) {
  trackOrder(tracking_number: $trackingNumber) {
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

### Mutations

```graphql
mutation CreateOrder($customerId: String!, $itemDesc: String!) {
  createOrder(
    customer_id: $customerId
    item_description: $itemDesc
  ) {
    id
    tracking_number
    status
    histories {
      location
      description
    }
  }
}

mutation UpdateTracking(
  $trackingNumber: String!
  $location: String!
  $description: String!
  $status: String!
) {
  updateTracking(
    tracking_number: $trackingNumber
    location: $location
    description: $description
    status: $status
  ) {
    id
    tracking_number
    status
  }
}
```

## 📦 RabbitMQ Events Published

- `order_created_queue` - When order is created

## 🗄️ Database Schema

**PostgreSQL Database**

- `orders` - Order records
- `tracking_histories` - Tracking history for each order

## 🚀 Setup

```bash
# Install dependencies
npm install

# Copy .env
cp .env.example .env

# Run migrations
npx prisma migrate deploy

# Generate Prisma client
npx prisma generate

# Start server
npm run dev
```

## 🐳 Docker

Service runs on port **4000** in Docker Compose.

## 🔍 GraphQL Playground

Access GraphQL Playground at: http://localhost:4000/graphql

---

**Part of Fleet Delivery System - Tugas Besar IAE 2026**
