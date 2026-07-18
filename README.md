# Fullstack Engineer Assessment Test Solutions

This repository contains the complete solutions for the Fullstack Engineer Assessment Test, implemented in **PHP 8.3**.

---

## Technical Design: Task 1 (Online Store API)

The API is located **directly at the root** of this repository. It is built using **Laravel 11** and **MySQL**.

### 1. Architecture (Clean Architecture & SOLID)
The application has been refactored from a controller-only layout into a decoupled enterprise architecture:
- **Form Request (`StoreOrderRequest`)**: Encapsulates incoming payload validation and validation message logic.
- **Repository Layer (`ProductRepository`, `OrderRepository`)**: Abstracts database queries, insulating business services from Eloquent implementation details.
- **Service Layer (`OrderService`)**: Contains core business logic. Manages database transactions, stock verification, and locks.
- **Dependency Injection (`RepositoryServiceProvider`)**: Automatically registers contract interfaces to concrete repository and service classes.

### 2. Concurrency Safety & Deadlock Prevention
- **Race Condition Handling**: Wrap order processing in database transactions (`DB::transaction`) and employ **pessimistic row-locking** (`SELECT ... FOR UPDATE` via `lockForUpdate()`). Any concurrent request for the same product blocks until the preceding transaction finishes, ensuring stock never drops below zero.
- **Deadlock Prevention**: Product IDs are dynamically **sorted ascending** before locks are acquired, ensuring all concurrent threads acquire locks in the exact same sequence.

### 3. API Endpoints
- **List Products**: `GET /api/products` (Accept: `application/json`)
- **Place Order**: `POST /api/orders` (Accept/Content-Type: `application/json`)
  - Payload:
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

---

## Technical Design: Task 2 (Hidden Item Game CLI)

The standalone CLI script is located in the [hidden-item-cli/](file:///Volumes/Extreme-SSD/ASSESMENT%20TEST%20FOMO/hidden-item-cli) directory.

### 1. Design & Logic
- Reads a hardcoded 6x8 board layout.
- Locates starting marker `X` (Row 4, Col 1).
- Runs an exhaustive search exploring valid movement counts for `A` (North), `B` (East), and `C` (South) while verifying no steps pass through obstacles (`#`).
- Outputs coordinates in two layouts to remove ambiguity:
  - **Grid**: 0-indexed `(Row, Column)` from Top-Left corner.
  - **Cartesian**: 1-indexed `(X, Y)` from Bottom-Left corner.
- Marks probable coordinates with `$` symbols in the printed terminal layout.

---

## Setup & Running Guide

### Prerequisites
- PHP >= 8.2 (configured locally)
- Composer
- MySQL/MariaDB database server

### 1. Database Setup
Ensure MySQL is running and create the databases:
```sql
CREATE DATABASE IF NOT EXISTS online_store_db;
CREATE DATABASE IF NOT EXISTS online_store_test_db;
```

### 2. Environment Setup
Copy `.env.example` to `.env` (already done at root) and configure your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_store_db
DB_USERNAME=root
DB_PASSWORD=your_password
```
Make sure `.env.testing` matches for testing:
```env
DB_DATABASE=online_store_test_db
```

### 3. Install Dependencies & Seed Database
From the root directory:
```bash
composer install
php artisan migrate
php artisan db:seed
```

### 4. Run Development Server
```bash
php artisan serve
```

---

## Verification & Testing

### 1. Running the Concurrency Race Condition Test
We have written a functional concurrency test in [tests/Feature/RaceConditionTest.php](file:///Volumes/Extreme-SSD/ASSESMENT%20TEST%20FOMO/tests/Feature/RaceConditionTest.php) which boots a local server on port 8085 using `online_store_test_db`, fires 20 parallel HTTP POST requests via `curl_multi`, and asserts that exactly 5 succeed (stock limit) while 15 are safely rejected.

To run:
```bash
php artisan test tests/Feature/RaceConditionTest.php
```

### 2. Running Task 2 (Hidden Item CLI)
To run the CLI program:
```bash
php hidden-item-cli/hidden_item.php
```
