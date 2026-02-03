# Multi-Tenant Database Architecture & Implementation Summary

This document provides a comprehensive overview of the **Database-per-Tenant** architecture implemented in this Laravel 12 application, including the recent redirection updates.

## 1. Core Architecture & Configuration

The system uses a strict **Database-per-Tenant** model. This ensures total data isolation between tenants (users).

| File Hierarchy | Component / Function | Concept / Role | Description of Change |
| :--- | :--- | :--- | :--- |
| **`config/`**<br>`database.php` | `connections` array | **Dual-Connection Strategy** | Defined two distinct connections:<br>1. **`landlord`**: Fixed connection to central DB (users, tenants).<br>2. **`tenant`**: Dynamic "template" connection (DB name is `null` initially). |
| **Root**<br>`.env` | `DB_CONNECTION` | **System Default** | Set `DB_CONNECTION=landlord` to ensure the app boots using the central database for authentication. |
| **`app/Models/`**<br>`Tenant.php` | `Tenant` Class | **Tenant Entity** | Created Eloquent model mapping to `tenants` table. Defines relationship: `User hasOne Tenant`. |

## 2. Database Provisioning (Registration Flow)

When a new user registers, a dedicated database is automatically created and migrated for them.

| File Hierarchy | Component / Function | Concept / Role | Description of Change |
| :--- | :--- | :--- | :--- |
| **`app/Services/`**<br>`TenantProvisioningService.php` | `createTenant(User $user)` | **Dynamic Provisioning** | 1. Generates unique DB name (`tenant_{uuid}`).<br>2. Creates `tenants` record.<br>3. Executes raw SQL `CREATE DATABASE`.<br>4. Calls `migrateTenant` to build schema. |
| **`app/Services/`**<br>`TenantProvisioningService.php` | `migrateTenant(Tenant $tenant)` | **Schema Cloning** | Dynamically configures the `tenant` connection to the new DB and runs `Artisan::call('migrate')` targeting `database/migrations/tenant`. |
| **`app/Http/Controllers/Auth/`**<br>`RegisteredUserController.php` | `store(Request $request)` | **Trigger Point** | Injected `TenantProvisioningService`. After user creation, immediately triggers `createTenant($user)` to ensure 1:1 parity. |

## 3. Runtime Isolation (The "Switch")

After login, the application automatically switches the database connection based on the authenticated user.

| File Hierarchy | Component / Function | Concept / Role | Description of Change |
| :--- | :--- | :--- | :--- |
| **`app/Http/Middleware/`**<br>`InitializeTenantContext.php` | `handle(Request $request)` | **Context Resolution** | Intercepts requests. If user is logged in:<br>1. Reads `user->tenant->database_name`.<br>2. Overrides `config('database.connections.tenant.database')`.<br>3. **Purges & Reconnects** DB to enforce isolation. |
| **`bootstrap/`**<br>`app.php` | `withMiddleware` | **Global Scope** | Registered `InitializeTenantContext` to the `web` and `api` middleware groups to ensure it runs on every authenticated request. |

## 4. Migration Strategy

Migrations are split into two distinct directories to prevent cross-contamination.

| File Hierarchy | Component / Function | Concept / Role | Description of Change |
| :--- | :--- | :--- | :--- |
| **`database/migrations/`**<br>`landlord/` | Directory | **System Schema** | Houses `users`, `tenants`, `jobs`, `cache`. These run only on the central database. |
| **`database/migrations/`**<br>`tenant/` | Directory | **Template Schema** | Houses `tasks`, `invoices`, etc. These are *never* run directly; they are applied to tenant DBs dynamically. |
| **`app/Providers/`**<br>`AppServiceProvider.php` | `boot()` | **Path Discovery** | Added `$this->loadMigrationsFrom(...)` to explicitly tell Laravel to look in `database/migrations/landlord` for the default `php artisan migrate` command. |

## 5. Navigation & Redirection Updates

The default dashboard route has been changed from `/dashboard` to `/index2`.

| File Hierarchy | Component / Function | Concept / Role | Description of Change |
| :--- | :--- | :--- | :--- |
| **`routes/`**<br>`web.php` | Route Definition | **Routing** | 1. Named the `/index2` route (`->name('index2')`).<br>2. Added `/test-tenant` route to verify DB isolation. |
| **`bootstrap/`**<br>`app.php` | `redirectTo` | **Middleware Default** | Configured global default redirection: Users → `/index2`, Guests → `/login`. |
| **`app/Http/Controllers/Auth/`**<br>`AuthenticatedSessionController.php` | `store` (Login) | **Login Redirect** | Changed redirection from `/dashboard` to `route('index2')`. |
| **`app/Http/Controllers/Auth/`**<br>`RegisteredUserController.php` | `store` (Register) | **Register Redirect** | Changed redirection from `/dashboard` to `route('index2')`. |
| **`app/Http/Controllers/Auth/`**<br>`*Controller.php` | Various Auth Controllers | **Consistency** | Updated `VerifyEmail`, `ConfirmablePassword`, etc., to all point to `/index2` instead of `/dashboard`. |

## 6. Verification

| File Hierarchy | Component / Function | Concept / Role | Description of Change |
| :--- | :--- | :--- | :--- |
| **Root**<br>`verify_tenancy.php` | Script | **E2E Testing** | A standalone CLI script created to verify the entire lifecycle (User Create -> DB Provision -> Context Switch -> Data Write) without needing a browser. |
