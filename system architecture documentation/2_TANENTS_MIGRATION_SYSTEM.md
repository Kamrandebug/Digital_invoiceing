# Multi-Tenancy Database Migration System

This document outlines the architecture, usage, and implementation details of the automated database migration system designed for the multi-tenant application.

## Overview

The system employs a **Database-per-Tenant** architecture. To manage schema changes across the fleet of tenant databases, we have implemented a centralized automation system that handles:
1.  **Provisioning**: Automatically migrating new tenant databases.
2.  **Deployment**: Batch-updating existing tenant databases.
3.  **Tracking**: Centralized logging of migration status for every tenant.
4.  **Recovery**: Support for rollbacks and dry-runs.

---

## Architecture

### 1. Centralized Logging
A `tenant_migration_logs` table exists in the **Landlord** database. This serves as the source of truth for the migration state of the entire fleet.

**Schema:**
- `tenant_id`: Foreign key to the `tenants` table.
- `migration`: Name of the migration file.
- `batch`: The batch number of the execution.
- `status`: `success`, `failed`, or `rolled_back`.
- `error_message`: Captures exception details on failure.
- `started_at` / `completed_at`: Timestamps for audit trails.

### 2. Migration Runner (`MigrateTenantJob`)
A queued job responsible for the atomic execution of migrations for a single tenant.
- **Dynamic Connection**: Switches the `tenant` database connection at runtime.
- **Sync Logic**: After execution, it compares the tenant's local `migrations` table with the centralized log to ensure accuracy.
- **Error Handling**: Catches exceptions, logs them centrally, and re-throws them for queue retry policies.

### 3. Console Command (`tenants:migrate`)
An Artisan command that orchestrates the mass deployment of migrations. It queries the tenant list and dispatches jobs.

---

## Usage Guide

### Standard Deployment (Migrate)
To apply pending migrations to all tenants:
```bash
php artisan tenants:migrate
```

### Dry Run (Preview)
To see what would happen without actually modifying any databases:
```bash
php artisan tenants:migrate --dry-run
```

### Rollback
To revert the last batch of migrations for all tenants:
```bash
php artisan tenants:migrate --rollback
```
*Optional: Use `--step=1` to rollback a specific number of steps.*

### Refresh (Reset & Re-run)
To wipe and re-run all migrations (useful for dev environments):
```bash
php artisan tenants:migrate --refresh
```

### Targeting Specific Tenants
To run any of the above operations on a specific tenant (e.g., for troubleshooting):
```bash
php artisan tenants:migrate --tenant=1
```

### Batch Processing
For large fleets, control the chunk size to manage memory usage:
```bash
php artisan tenants:migrate --batch-size=50
```

---

## Implementation Details

### Tenant Provisioning Service
The `TenantProvisioningService` has been updated to integrate with this system. When a new tenant is created:
1.  The database is created.
2.  `migrateTenant()` is called immediately.
3.  Logs are synced to `tenant_migration_logs` instantly, ensuring new tenants effectively start with a "clean slate" in the logs.

### Log Synchronization Logic
The `syncMigrationLogs` method in `MigrateTenantJob` ensures consistency:
1.  **Success**: Existing migrations in the tenant DB are upserted as `success` in the central log.
2.  **Rollback**: Migrations present in the central log but **missing** from the tenant DB are marked as `rolled_back`.

---

## Troubleshooting

**View Migration History**
Check the `tenant_migration_logs` table:
```sql
SELECT * FROM tenant_migration_logs WHERE tenant_id = 1 ORDER BY id DESC;
```

**Failed Migrations**
If a migration fails, the `status` will be `failed` and `error_message` will contain the exception. The job will fail in the queue.
1.  Fix the migration file or data issue.
2.  Retry the job via the queue worker, or re-run the `tenants:migrate` command.
