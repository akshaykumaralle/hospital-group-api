# Hospital & Clinician Group Management API

This is a RESTful API built with Laravel 11/12 designed to manage a hierarchical tree structure of healthcare organizations, specifically **Hospitals** and nested **Clinician Groups**.

The API enforces strict data integrity rules, preventing circular references and ensuring a clean group hierarchy.

## 🚀 Features

* **Hierarchical Management:** Groups can be nested infinitely using a `parent_id` relationship.

* **Data Integrity:** Implements checks to prevent:

  * **Deletion Conflict:** A parent group cannot be deleted if it has active child groups (**409 Conflict**).

  * **Circular References:** A group cannot be set as its own parent or an ancestor (**422 Unprocessable Entity**).

  * **Unique Sibling Names:** Group names must be unique under the same parent.

* **Tree Retrieval:** The `GET /api/groups` endpoint returns the full organization structure, rooted at the top-level hospitals.

* **Custom Exception Handling:** Catches critical system failures (`QueryException`, `PDOException`) and returns standardized, non-revealing JSON error messages.

## 🛠️ Installation and Setup

### Prerequisites

* PHP (8.2+)

* Composer

* MySQL Server (Used for both development and testing to ensure data fidelity)

### Steps

1. **Clone the Repository:**
    ```bash
    git clone https://github.com/akshaykumaralle/hospital-group-api.git
    cd hospital-group-api
    ```


2. **Install PHP Dependencies:**
    ```bash
    composer install
    ```


3. **Configure Environment:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Update your `.env` file with your **development database** credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

4. **Configure Testing Environment:**

    This project uses a dedicated MySQL database for feature testing to ensure database fidelity.

   * **Create Test Database:** 

        Log into MySQL and create an empty database (e.g., `hospital_group_test`).

        ```sql
        CREATE DATABASE hospital_group_test;
        ```

   * Update the `phpunit.xml` file with your MySQL credentials for the test environment.

5. **Run Migrations:**

    Run migrations for the development database.
    ```bash
    php artisan migrate
    ```


## 🧪 Testing

The project includes robust **Feature (Integration) Tests** to validate all API endpoints, data integrity rules, and database interactions.

1. **Run all tests:**
    ```bash
    php artisan test
    ```


    *Note: The `RefreshDatabase` trait ensures the dedicated test database is cleaned before each test run.*

## 📚 API Endpoints and Documentation

All available endpoints, expected request formats, and specific custom error responses are detailed in the official API documentation.

* **View API Documentation:** **`API DOCUMENTATION.md`**

This documentation includes:

* CRUD operations for `/api/groups`.

* JSON structures for success and error responses.

* Details on the `409 Conflict` (cannot delete with children) and `422 Unprocessable Entity` (circular reference) responses.