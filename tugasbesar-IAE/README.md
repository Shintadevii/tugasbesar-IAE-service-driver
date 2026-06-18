# 🔐 Service 1: Auth & Customer Service

**Technology Stack:** Laravel 13 + MySQL + Laravel Sanctum + RabbitMQ

---

## 📋 Description

Service untuk manajemen autentikasi dan data customer/agent. Menggunakan RESTful API dengan Laravel Sanctum untuk JWT authentication.

## 🎯 Features

- ✅ User Authentication (Register, Login, Logout)
- ✅ Customer Management (CRUD)
- ✅ Agent Management (CRUD)
- ✅ JWT Token via Laravel Sanctum
- ✅ RabbitMQ Event Publishing

## 🔌 API Endpoints

**Base URL:** `http://localhost:3000/api`

### Auth
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login user
- `GET /auth/me` - Get current user (authenticated)
- `POST /auth/logout` - Logout user (authenticated)

### Customer
- `GET /customers` - Get all customers (authenticated)
- `POST /customers` - Create customer (authenticated)
- `GET /customers/{id}` - Get customer by ID (authenticated)

### Agent
- `GET /agents` - Get all agents (authenticated)
- `POST /agents` - Create agent (authenticated)
- `GET /agents/{id}` - Get agent by ID (authenticated)

## 📦 RabbitMQ Events Published

- `user_registered_queue` - When user registers
- `customer_created_queue` - When customer is created
- `agent_created_queue` - When agent is created

## 🗄️ Database Schema

**MySQL Database**

- `users` - User accounts (name, email, password, role)
- `customers` - Customer profiles
- `agents` - Agent profiles
- `personal_access_tokens` - Sanctum tokens

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

# Start server
php artisan serve
```

## 🐳 Docker

Service runs on port **3000** in Docker Compose.

## 🔐 Authentication

Uses Laravel Sanctum Bearer Token:
```
Authorization: Bearer {token}
```

---

**Part of Fleet Delivery System - Tugas Besar IAE 2026**
