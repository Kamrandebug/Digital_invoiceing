<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardsController::class, 'index2'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/index2', [DashboardsController::class, 'index2'])->name('index2');
    Route::get('/customers-list', [CustomerController::class, 'index'])->middleware('tenant');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->get('/test-tenant', function () {
    $task = Task::create(['title' => 'Test Task ' . uniqid()]);
    return response()->json([
        'database'  => DB::connection()->getDatabaseName(),
        'tenant_id' => optional(auth()->guard()->user())->tenant->id ?? null,
        'task'      => $task,
        'all_tasks' => Task::all(),
    ]);
});

// ── Customers ─────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('customers')->name('customers.')->group(function () {

    Route::get('/',                [CustomerController::class, 'index'])            ->name('index');
    Route::get('/create',          [CustomerController::class, 'create'])           ->name('create');
    Route::post('/save',           [CustomerController::class, 'save_transaction']) ->name('save_transaction');

    // ⚠️ Static segments BEFORE /{id} wildcard
    Route::get('/export/csv',      [CustomerController::class, 'exportCSV'])        ->name('export-csv');
    Route::get('/modal',           [CustomerController::class, 'modal'])            ->name('modal');

    Route::get('/{id}/edit',       [CustomerController::class, 'edit'])             ->name('edit');
    Route::put('/{id}/update',     [CustomerController::class, 'edit_transaction']) ->name('edit_transaction');
    Route::delete('/{id}/delete',  [CustomerController::class, 'delete_transaction'])->name('delete_transaction');
    Route::get('/{id}/modal',      [CustomerController::class, 'modal'])            ->name('modal.id');
    Route::get('/{id}',            [CustomerController::class, 'show'])             ->name('show');
});

// ── Business Info ─────────────────────────────────────────────
use App\Http\Controllers\BusinessInfoController;

Route::middleware(['auth', 'tenant'])->prefix('business')->name('business.')->group(function () {
    Route::get('/',               [BusinessInfoController::class, 'index'])->name('index');
    Route::post('/save',          [BusinessInfoController::class, 'save_transaction'])->name('save_transaction');
    Route::get('/modal',          [BusinessInfoController::class, 'modal'])->name('modal');
    Route::get('/{id}/modal',     [BusinessInfoController::class, 'modal'])->name('modal.id');
    Route::put('/{id}/update',    [BusinessInfoController::class, 'edit_transaction'])->name('edit_transaction');
    Route::delete('/{id}/delete', [BusinessInfoController::class, 'delete_transaction'])->name('delete_transaction');
});

// ── Products ──────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('products')->name('products.')->group(function () {

    Route::get('/',                [ProductController::class, 'index'])             ->name('index');
    Route::get('/create',          [ProductController::class, 'create'])            ->name('create');
    Route::post('/save',           [ProductController::class, 'save_transaction'])  ->name('save_transaction');
    Route::get('/hscode-info',         [ProductController::class, 'hsCodeInfo'])        ->name('hscode-info');
    Route::get('/sale-types',          [ProductController::class, 'saleTypes'])         ->name('sale-types');
    Route::get('/sale-type-to-rates',  [ProductController::class, 'saleTypeToRates'])    ->name('sale-type-to-rates');
    Route::get('/sro-schedules',      [ProductController::class, 'sroSchedules'])         ->name('sro-schedules');
    Route::get('/sro-items',         [ProductController::class, 'sroItems'])            ->name('sro-items');

    Route::get('/export/csv',      [ProductController::class, 'exportCSV'])         ->name('exportCSV');
    Route::get('/modal',          [ProductController::class, 'modal'])             ->name('modal');
    Route::get('/{id}/modal',     [ProductController::class, 'modal'])              ->name('modal.id');

    Route::get('/{id}/edit',       [ProductController::class, 'edit'])              ->name('edit');
    Route::put('/{id}/update',     [ProductController::class, 'edit_transaction'])  ->name('edit_transaction');
    Route::delete('/{id}/delete',  [ProductController::class, 'delete_transaction'])->name('delete_transaction');
    Route::get('/{id}',            [ProductController::class, 'show'])              ->name('show');
});

// ── Invoices ──────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('invoices')->name('invoices.')->group(function () {

    Route::get('/',                    [InvoiceController::class, 'index'])               ->name('index');
    Route::get('/create',              [InvoiceController::class, 'create'])              ->name('create');
    Route::post('/save',               [InvoiceController::class, 'save_transaction'])    ->name('save_transaction');

    // ⚠️ Static segments BEFORE /{id} wildcard
    Route::get('/export/csv',          [InvoiceController::class, 'exportCSV'])           ->name('export-csv');
    Route::get('/modal',               [InvoiceController::class, 'modal'])               ->name('modal');

    Route::get('/{id}/edit',           [InvoiceController::class, 'edit'])                ->name('edit');
    Route::post('/{id}/update',        [InvoiceController::class, 'edit_transaction'])    ->name('edit_transaction');
    Route::delete('/{id}/delete',      [InvoiceController::class, 'delete_transaction'])  ->name('delete_transaction');
    Route::get('/{id}/modal',          [InvoiceController::class, 'modal'])               ->name('modal.id');
    Route::get('/{id}',                [InvoiceController::class, 'show'])                ->name('show');
});

require __DIR__ . '/auth.php';