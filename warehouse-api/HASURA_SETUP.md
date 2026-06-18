# 🚀 Hasura GraphQL Setup untuk Warehouse Service

## Akses Hasura Console

Setelah `docker-compose up`, akses Hasura Console di:
```
http://localhost:8080
```

**Admin Secret**: `adminsecret123`

---

## 📋 Langkah Setup Hasura

### 1. Track Tables

Di Hasura Console:
1. Pergi ke tab **DATA**
2. Pilih database **default**
3. Track semua tables:
   - `products`
   - `stocks`
   - `orders`

### 2. Setup Relationships

#### Table: `stocks`
- **Relationship Name**: `product`
- **Type**: Object Relationship
- **Reference**: `stocks.product_id → products.id`

#### Table: `orders`
- **Relationship Name**: `product`
- **Type**: Object Relationship
- **Reference**: `orders.product_id → products.id`

#### Table: `products`
- **Relationship Name**: `stocks`
- **Type**: Array Relationship
- **Reference**: `products.id ← stocks.product_id`

- **Relationship Name**: `orders`
- **Type**: Array Relationship
- **Reference**: `products.id ← orders.product_id`

---

## 🧪 Contoh GraphQL Queries untuk Testing

### Query 1: Get All Products dengan Stock
```graphql
query GetAllProductsWithStock {
  products {
    id
    sku
    name
    description
    price
    category
    stocks {
      id
      location
      quantity
      reorder_level
    }
  }
}
```

### Query 2: Get All Warehouse Orders
```graphql
query GetWarehouseOrders {
  orders {
    id
    reference
    status
    quantity
    total
    created_at
    product {
      name
      sku
      category
    }
  }
}
```

### Query 3: Get Low Stock Items
```graphql
query GetLowStockItems {
  stocks(where: {_and: [{quantity: {_lte: 5}}]}) {
    id
    location
    quantity
    reorder_level
    product {
      name
      sku
      category
    }
  }
}
```

### Query 4: Get Specific Order by Reference (Tracking Number)
```graphql
query GetOrderByTracking($trackingNumber: String!) {
  orders(where: {reference: {_eq: $trackingNumber}}) {
    id
    reference
    status
    quantity
    product {
      name
      description
    }
    created_at
    updated_at
  }
}
```

**Variables:**
```json
{
  "trackingNumber": "REG-1234567890-123"
}
```

### Mutation 1: Update Order Status
```graphql
mutation UpdateOrderStatus($orderId: Int!, $status: String!) {
  update_orders_by_pk(
    pk_columns: {id: $orderId}, 
    _set: {status: $status}
  ) {
    id
    reference
    status
    updated_at
  }
}
```

**Variables:**
```json
{
  "orderId": 1,
  "status": "processing"
}
```

### Mutation 2: Update Stock Quantity
```graphql
mutation UpdateStockQuantity($stockId: Int!, $newQuantity: Int!) {
  update_stocks_by_pk(
    pk_columns: {id: $stockId}, 
    _set: {quantity: $newQuantity}
  ) {
    id
    quantity
    location
    product {
      name
    }
  }
}
```

---

## 🔗 GraphQL Endpoint

Untuk integrasi dengan service lain:
```
http://localhost:8080/v1/graphql
```

**Headers:**
```
x-hasura-admin-secret: adminsecret123
Content-Type: application/json
```

---

## 📝 Notes untuk Presentasi

1. **Hasura = GraphQL tanpa coding manual**
   - Otomatis generate GraphQL dari database schema
   - Real-time subscriptions support
   - Built-in authorization

2. **Keuntungan untuk Warehouse Service**
   - Query fleksibel untuk monitoring stock
   - Real-time updates ketika ada perubahan inventory
   - Filter & sorting sudah built-in

3. **Integration dengan Service Lain**
   - Order Service bisa query warehouse stock via GraphQL
   - Driver Service bisa check status dispatch
   - Auth Service bisa verify access dengan Hasura permissions
