<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleReportController;
use App\Http\Controllers\TenantListController;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardsController::class, 'index2'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('dashboard');

// ── Tenants (list + select; no tenant middleware) ────────────
Route::middleware('auth')->prefix('tenants')->name('tenants.')->group(function () {
    Route::get('/', [TenantListController::class, 'index'])->name('index');
    Route::post('/add', [TenantListController::class, 'add'])->name('add');
    Route::get('/{id}/select', [TenantListController::class, 'select'])->name('select');
});

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('/index2', [DashboardsController::class, 'index2'])->name('index2');
    Route::get('/customers-list', [CustomerController::class, 'index'])->middleware('tenant');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->get('/test-tenant', function () {
    $task = Task::create(['title' => 'Test Task '.uniqid()]);

    return response()->json([
        'database' => DB::connection()->getDatabaseName(),
        'tenant_id' => optional(auth()->guard()->user())->tenant->id ?? null,
        'task' => $task,
        'all_tasks' => Task::all(),
    ]);
});

// ── Customers ─────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('customers')->name('customers.')->group(function () {

    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->middleware('tenant.role:owner,admin,accountant')->name('create');
    Route::post('/save', [CustomerController::class, 'save_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('save_transaction');

    // ⚠️ Static segments BEFORE /{id} wildcard
    Route::get('/export/csv', [CustomerController::class, 'exportCSV'])->name('export-csv');
    Route::post('/verify-fbr', [CustomerController::class, 'verifyFbr'])->middleware('tenant.role:owner,admin,accountant')->name('verify_fbr');
    Route::get('/modal', [CustomerController::class, 'modal'])->name('modal');

    Route::get('/{id}/edit', [CustomerController::class, 'edit'])->middleware('tenant.role:owner,admin,accountant')->name('edit');
    Route::put('/{id}/update', [CustomerController::class, 'edit_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('edit_transaction');
    Route::delete('/{id}/delete', [CustomerController::class, 'delete_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('delete_transaction');
    Route::get('/{id}/modal', [CustomerController::class, 'modal'])->name('modal.id');
    Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
});

// ── Suppliers (same tbl_customer / temp_customer, PartyType = 2) ─
Route::middleware(['auth', 'tenant'])->prefix('suppliers')->name('suppliers.')->group(function () {

    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/create', [SupplierController::class, 'create'])->middleware('tenant.role:owner,admin,accountant')->name('create');
    Route::post('/save', [SupplierController::class, 'save_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('save_transaction');

    Route::get('/export/csv', [SupplierController::class, 'exportCSV'])->name('export-csv');
    Route::post('/verify-fbr', [SupplierController::class, 'verifyFbr'])->middleware('tenant.role:owner,admin,accountant')->name('verify_fbr');
    Route::get('/modal', [SupplierController::class, 'modal'])->name('modal');

    Route::get('/{id}/edit', [SupplierController::class, 'edit'])->middleware('tenant.role:owner,admin,accountant')->name('edit');
    Route::put('/{id}/update', [SupplierController::class, 'edit_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('edit_transaction');
    Route::delete('/{id}/delete', [SupplierController::class, 'delete_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('delete_transaction');
    Route::get('/{id}/modal', [SupplierController::class, 'modal'])->name('modal.id');
    Route::get('/{id}', [SupplierController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('/suppliers-list', [SupplierController::class, 'index'])->middleware('tenant');
});

// ── Purchases ─────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('purchases')->name('purchases.')->group(function () {

    Route::get('/', [PurchaseController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseController::class, 'create'])->middleware('tenant.role:owner,admin,accountant')->name('create');
    Route::post('/save', [PurchaseController::class, 'save_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('save_transaction');

    Route::get('/export/csv', [PurchaseController::class, 'exportCSV'])->name('export-csv');
    Route::get('/modal', [PurchaseController::class, 'modal'])->name('modal');

    Route::get('/{id}/edit', [PurchaseController::class, 'edit'])->middleware('tenant.role:owner,admin,accountant')->name('edit');
    Route::put('/{id}/update', [PurchaseController::class, 'edit_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('edit_transaction');
    Route::delete('/{id}/delete', [PurchaseController::class, 'delete_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('delete_transaction');
    Route::get('/{id}/modal', [PurchaseController::class, 'modal'])->name('modal.id');
    Route::get('/{id}', [PurchaseController::class, 'show'])->name('show');
});

// ── Business Info ─────────────────────────────────────────────
use App\Http\Controllers\BusinessInfoController;

Route::middleware(['auth', 'tenant'])->prefix('business')->name('business.')->group(function () {
    Route::get('/', [BusinessInfoController::class, 'index'])->name('index');
    Route::get('/modal', [BusinessInfoController::class, 'modal'])->middleware('tenant.role:owner,admin')->name('modal');
    Route::get('/{id}/modal', [BusinessInfoController::class, 'modal'])->middleware('tenant.role:owner,admin')->name('modal.id');
    Route::put('/{id}/update', [BusinessInfoController::class, 'edit_transaction'])->middleware('tenant.role:owner,admin')->name('edit_transaction');
});

// ── Products ──────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('products')->name('products.')->group(function () {

    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->middleware('tenant.role:owner,admin,accountant')->name('create');
    Route::post('/save', [ProductController::class, 'save_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('save_transaction');
    Route::get('/hscode-info', [ProductController::class, 'hsCodeInfo'])->name('hscode-info');
    Route::get('/sale-types', [ProductController::class, 'saleTypes'])->name('sale-types');
    Route::get('/sale-type-to-rates', [ProductController::class, 'saleTypeToRates'])->name('sale-type-to-rates');
    Route::get('/sro-schedules', [ProductController::class, 'sroSchedules'])->name('sro-schedules');
    Route::get('/sro-items', [ProductController::class, 'sroItems'])->name('sro-items');
    Route::get('/lookups/categories-units', [ProductController::class, 'lookupsCategoriesUnits'])->name('lookups.categories-units');
    Route::post('/lookups/category', [ProductController::class, 'storeQuickCategory'])->middleware('tenant.role:owner,admin,accountant')->name('lookups.category-store');
    Route::post('/lookups/unit', [ProductController::class, 'storeQuickUnit'])->middleware('tenant.role:owner,admin,accountant')->name('lookups.unit-store');

    Route::get('/export/csv', [ProductController::class, 'exportCSV'])->name('exportCSV');
    Route::get('/modal', [ProductController::class, 'modal'])->name('modal');
    Route::get('/{id}/modal', [ProductController::class, 'modal'])->name('modal.id');

    Route::get('/{id}/edit', [ProductController::class, 'edit'])->middleware('tenant.role:owner,admin,accountant')->name('edit');
    Route::put('/{id}/update', [ProductController::class, 'edit_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('edit_transaction');
    Route::delete('/{id}/delete', [ProductController::class, 'delete_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('delete_transaction');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
});

// ── Invoices ──────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('invoices')->name('invoices.')->group(function () {

    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/create', [InvoiceController::class, 'create'])->middleware('tenant.role:owner,admin,accountant')->name('create');
    Route::post('/save', [InvoiceController::class, 'save_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('save_transaction');

    // ⚠️ Static segments BEFORE /{id} wildcard
    Route::get('/export/csv', [InvoiceController::class, 'exportCSV'])->name('export-csv');
    Route::get('/modal', [InvoiceController::class, 'modal'])->name('modal');

    Route::get('/{id}/edit', [InvoiceController::class, 'edit'])->middleware('tenant.role:owner,admin,accountant')->name('edit');
    Route::post('/{id}/update', [InvoiceController::class, 'edit_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('edit_transaction');
    Route::delete('/{id}/delete', [InvoiceController::class, 'delete_transaction'])->middleware('tenant.role:owner,admin,accountant')->name('delete_transaction');
    Route::get('/{id}/modal', [InvoiceController::class, 'modal'])->name('modal.id');
    Route::get('/{id}/print-view', [InvoiceController::class, 'printView'])->name('print-view');
    Route::post('/{id}/post-fbr', [InvoiceController::class, 'postToFbr'])->middleware('tenant.role:owner,admin,accountant')->name('post-fbr');
    Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
});

// ── Reports ───────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/sale', [SaleReportController::class, 'index'])->name('sale');
    Route::get('/sale/export', [SaleReportController::class, 'exportCsv'])->name('sale.export');
    Route::get('/sale/print', [SaleReportController::class, 'printView'])->name('sale.print');
});

require __DIR__.'/auth.php';
