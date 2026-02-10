# Digital Invoicing System Documentation

## 1. System Overview
The **Digital Invoicing System** is a Laravel-based web application designed for multi-tenant invoice and customer management. It provides functionality for businesses to manage their customers, track balances, and handle invoicing operations. The system uses a multi-tenant database architecture where each tenant's data is isolated.

## 2. Key Technologies
- **Backend Framework**: Laravel 11.x
- **Frontend**: Blade Templates, Bootstrap 5, Custom CSS (`customer-management.css`)
- **Database**: MySQL (Multi-tenant architecture)
- **Scripting**: jQuery / Vanilla JS

## 3. Directory Structure
Key directories and files relevant to the application's core functionality:

```
k:\Digital_invoiceing\
├── app\
│   ├── Http\
│   │   ├── Controllers\
│   │   │   ├── CustomerController.php      # Handles all customer-related logic (CRUD)
│   │   │   ├── DashboardsController.php    # Handles main dashboard views
│   │   │   └── ...
│   │   ├── Middleware\
│   │   │   └── InitializeTenantContext.php # Ensures correct tenant DB connection
│   └── Models\
│       ├── Customer.php                    # Eloquent model for Customers table
│       └── ...
├── resources\
│   ├── views\
│   │   ├── pages\
│   │   │   └── dashboards\
│   │   │       ├── customers-list.blade.php    # Main customer listing table
│   │   │       ├── customers-create.blade.php  # Form to add new customer
│   │   │       ├── customers-edit.blade.php    # Form to edit existing customer
│   │   │       └── customers-show.blade.php    # Read-only view of customer details
│   ├── css\
│   │   └── customer-management.css         # Custom styles for customer pages
├── routes\
│   └── web.php                             # Application routes definition
└── ...
```

## 4. Customer Management Module

The Customer Management module is a core component allowing users to Create, Read, Update, and Delete (CRUD) customer records.

### 4.1. Data Flow
1.  **Request**: User clicks a link or submits a form.
2.  **Route**: `routes/web.php` intercepts the request and directs it to the appropriate Controller.
3.  **Middleware**: `tenant` middleware runs to ensure the request operates on the correct tenant's database.
4.  **Controller**: `CustomerController` processes the logic (fetching data, validating input, saving to DB).
5.  **View**: A Blade template renders the data into HTML for the user.

### 4.2. Detailed Workflow

#### A. Listing Customers
-   **URL**: `/customers-list` or `/customers`
-   **Route Name**: `customers.index`
-   **Controller Method**: `CustomerController::index()`
-   **View**: `customers-list.blade.php`
-   **Logic**: Fetches all active customers from the database and passes them to the view.
-   **Key Features**:
    -   Data Table with sorting columns.
    -   Action buttons (Edit, View, Delete).
    -   "Add New Customer" button.

#### B. Viewing Customer Details
-   **URL**: `/customers/{id}`
-   **Route Name**: `customers.show`
-   **Controller Method**: `CustomerController::show($id)`
-   **View**: `customers-show.blade.php`
-   **Logic**: Finds a customer by ID. If found, returns the read-only detail view. If not, throws a 404 error.
-   **Key Features**:
    -   Read-only display of all fields.
    -   "Edit Customer" shortcut button.
    -   "Back to List" navigation.

#### C. Creating a Customer
-   **URL (Form)**: `/customers/create`
-   **URL (Submit)**: `/customers/save` (POST)
-   **Route Name**: `customers.create` / `customers.save_transaction`
-   **Controller Method**: `CustomerController::create()` / `CustomerController::save_transaction()`
-   **View**: `customers-create.blade.php`
-   **Logic**: Validates input (required fields, max lengths), generates `AccountNo` automatically, and creates the record in the database.

#### D. Editing a Customer
-   **URL (Form)**: `/customers/{id}/edit`
-   **URL (Submit)**: `/customers/{id}/update` (PUT)
-   **Route Name**: `customers.edit` / `customers.edit_transaction`
-   **Controller Method**: `CustomerController::edit($id)` / `CustomerController::edit_transaction()`
-   **View**: `customers-edit.blade.php`
-   **Logic**: Pre-fills the form with existing data. Validates updates and saves changes.

#### E. Deleting a Customer
-   **URL**: `/customers/{id}/delete` (DELETE)
-   **Route Name**: `customers.delete_transaction`
-   **Controller Method**: `CustomerController::delete_transaction($id)`
-   **Logic**: Performs a "soft delete" or archives the customer record to preserve historical data.

## 5. Recent Fixes & Modifications

### 5.1. Undefined Variable `$customers` Fix
-   **Issue**: The `/customers-list` route was pointing to `DashboardsController` which didn't pass the `$customers` variable.
-   **Fix**: Updated `routes/web.php` to point `/customers-list` to `CustomerController::index`.
    ```php
    // Before
    Route::get('/customers-list', [DashboardsController::class, 'customers_list']);
    
    // After
    Route::get('/customers-list', [CustomerController::class, 'index'])->middleware('tenant');
    ```

### 5.2. Missing View `customers-show` Fix
-   **Issue**: Clicking "View Details" resulted in a "View not found" error.
-   **Fix**: Created `resources/views/pages/dashboards/customers-show.blade.php`.
    -   Designed a read-only layout matching the system's theme (Purple/Cyan).
    -   Included all customer fields (Basic Info, Contact, Address, Financials).
    -   Added navigation buttons for better UX.

## 6. How to Run & Maintain
1.  **Routes**: Always define new routes in `routes/web.php`. Ensure `tenant` middleware is applied if database access is needed.
2.  **Controllers**: Keep logic in `CustomerController.php`. Use `try-catch` blocks for database transactions.
3.  **Views**: Use Blade `@extends('layouts.master')` for consistency. Use `{{ route('name') }}` helper for links instead of hardcoded URLs.