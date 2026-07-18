# Task 1: Online Store API

This repository contains the solution for **Task 1: Online Store API**, built using **PHP 8.3** and **Laravel 11**.

---

## Technical Design & Concurrency Handling

### 1. Race Condition Resolution (Pessimistic Locking)
During a flash sale, multiple requests concurrently try to purchase the same product. If not handled correctly, they could read the inventory at the same time, see that stock exists, decrement it, and create orders, resulting in a **negative stock** (oversell).

To resolve this race condition:
- The order creation is wrapped in a database **transaction** (`DB::transaction`).
- Within the transaction, the product record is retrieved using a **pessimistic lock** (i.e. `SELECT ... FOR UPDATE` via Eloquent's `lockForUpdate()`).
- This block locks the queried rows. Any other database connection trying to query the same product with `lockForUpdate()` or write to it will block and wait until the transaction commits or rolls back.
- While locked, the system reads the absolute latest stock, verifies it against the requested quantity, decrements the stock safely, and records the order.
- This guarantees the inventory can never drop below zero.

### 2. Deadlock Prevention
If Order A wants to buy Product 1 and Product 2, and Order B concurrently wants to buy Product 2 and Product 1:
- Order A locks Product 1 and waits for Product 2.
- Order B locks Product 2 and waits for Product 1.
- This causes a database **deadlock**.

To prevent this:
- The array of requested items is **sorted by `product_id`** in ascending order before acquiring any locks.
- This ensures that concurrent requests always request locks in the exact same order, completely eliminating the possibility of deadlocks.

---

## API Documentation

### 1. List Products
- **Endpoint**: `GET /api/products`
- **Headers**:
  - `Accept: application/json`
- **Response**: `200 OK`
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "name": "Flash Sale Super Phone",
        "price": "199.99",
        "stock": 10,
        "created_at": "2026-07-18T17:25:44.000000Z",
        "updated_at": "2026-07-18T17:25:44.000000Z"
      }
    ]
  }
  ```

### 2. Place Order
- **Endpoint**: `POST /api/orders`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
- **Payload**:
  ```json
  {
    "items": [
      {
        "product_id": 1,
        "quantity": 1
      }
    ]
  }
  ```
- **Success Response**: `201 Created`
  ```json
  {
    "success": true,
    "message": "Order created successfully.",
    "data": {
      "id": 1,
      "total_amount": 199.99,
      "status": "completed",
      "created_at": "2026-07-18T17:30:35.000000Z",
      "updated_at": "2026-07-18T17:30:35.000000Z",
      "order_items": [
        {
          "id": 1,
          "order_id": 1,
          "product_id": 1,
          "quantity": 1,
          "price": "199.99",
          "product": {
            "id": 1,
            "name": "Flash Sale Super Phone",
            "price": "199.99",
            "stock": 9
          }
        }
      ]
    }
  }
  ```
- **Error Response (Out of Stock / Insufficient Inventory)**: `422 Unprocessable Entity`
  ```json
  {
    "success": false,
    "message": "Unable to process order due to stock constraints.",
    "error": "Product 'Flash Sale Super Phone' (ID: 1) is out of stock. Requested: 1, Available: 0."
  }
  ```

---

## Setup & Running Guide

### Prerequisites
- PHP >= 8.2
- Composer
- MySQL/MariaDB database server

### 1. Database Configuration
Ensure MySQL is running and create the databases:
```sql
CREATE DATABASE IF NOT EXISTS online_store_db;
CREATE DATABASE IF NOT EXISTS online_store_test_db;
```

### 2. Environment Setup
Clone the repository, enter the directory `online-store-api`, copy `.env.example` to `.env` (already done in this workspace), and configure database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_store_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

Configure `.env.testing` similarly for the test database:
```env
DB_DATABASE=online_store_test_db
```

### 3. Install Dependencies & Run Migrations
```bash
composer install
php artisan migrate
php artisan db:seed
```

### 4. Run the Development Server
```bash
php artisan serve
```

---

## Running the Concurrency Race Condition Test

A robust functional integration test has been implemented in `tests/Feature/RaceConditionTest.php`. It programmatically:
1. Bootstraps a temporary test server on port `8085` using the testing environment (`online_store_test_db`).
2. Seeds a product with exactly **5 items in stock**.
3. Employs `curl_multi` to fire **20 concurrent purchase requests** to the API.
4. Asserts that exactly **5 requests succeed (HTTP 201)** and **15 requests fail (HTTP 422)**.
5. Verifies the database contains exactly **5 order records** and the product stock is exactly **0** (preventing negative inventory).

Run it with:
```bash
php artisan test tests/Feature/RaceConditionTest.php
```
