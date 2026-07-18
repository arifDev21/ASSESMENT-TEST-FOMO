# Fullstack Engineer Assessment Test Solutions

This repository contains the complete solutions for the Assessment Test, implemented in **PHP 8.3**.

## Directory Structure

The project is structured into two self-contained directories:

1. **[Task 1: Online Store API](file:///Volumes/Extreme-SSD/ASSESMENT%20TEST%20FOMO/online-store-api)**
   - Built with Laravel 11 and MySQL.
   - Solves race conditions under heavy concurrent load using **database transactions** and **pessimistic locking** (`lockForUpdate`).
   - Includes a functional integration test that validates concurrency safety via parallel HTTP requests using `curl_multi`.
   - Read the [online-store-api README](file:///Volumes/Extreme-SSD/ASSESMENT%20TEST%20FOMO/online-store-api/README.md) for setup and API documentation.

2. **[Task 2: Hidden Item Game CLI](file:///Volumes/Extreme-SSD/ASSESMENT%20TEST%20FOMO/hidden-item-cli)**
   - Standalone PHP command-line script.
   - Solves grid traversal path finding under specific movement sequence rules (North, East, South) while avoiding obstacles (`#`).
   - Outputs probable coordinates in both Grid (0-indexed) and Cartesian (1-indexed) formats.
   - Prints the grid with the item locations marked with `$`.
   - Read the [hidden-item-cli README](file:///Volumes/Extreme-SSD/ASSESMENT%20TEST%20FOMO/hidden-item-cli/README.md) for usage instructions and coordinate details.

---

## Technical Validation

### 1. Task 1 Concurrency Test
Run the functional integration test to verify the race condition logic:
```bash
cd online-store-api
php artisan test tests/Feature/RaceConditionTest.php
```

### 2. Task 2 CLI Game
Run the grid-search program:
```bash
cd hidden-item-cli
php hidden_item.php
```
