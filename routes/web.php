<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
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
    // Create a task in the tenant DB
    $task = Task::create(['title' => 'Test Task ' . uniqid()]);

    return response()->json([
        'database' => DB::connection()->getDatabaseName(),
        'tenant_id' => optional(auth()->guard()->user())->tenant->id ?? null,
        'task' => $task,
        'all_tasks' => Task::all(),
    ]);
});

Route::middleware(['auth', 'tenant'])->prefix('customers')->name('customers.')->group(function () {
    // List customers
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    
    // Create customer
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::post('/save', [CustomerController::class, 'save_transaction'])->name('save_transaction');
    
    // Edit customer
    Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
    Route::put('/{id}/update', [CustomerController::class, 'edit_transaction'])->name('edit_transaction');
    
    // Delete customer
    Route::delete('/{id}/delete', [CustomerController::class, 'delete_transaction'])->name('delete_transaction');
    
    // Export
    Route::get('/export/csv', [CustomerController::class, 'exportCSV'])->name('export-csv');

    // View customer
    Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'tenant'])->prefix('products')->name('products.')->group(function () {
    // List products
    Route::get('/', [ProductController::class, 'index'])->name('index');

    // Create product
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/save', [ProductController::class, 'save_transaction'])->name('save_transaction');

    // Edit product
    Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{id}/update', [ProductController::class, 'edit_transaction'])->name('edit_transaction');

    // Delete product
    Route::delete('/{id}/delete', [ProductController::class, 'delete_transaction'])->name('delete_transaction');

    // Export
    Route::get('/export/csv', [ProductController::class, 'exportCSV'])->name('exportCSV');

    // View product
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
});

require __DIR__.'/auth.php';
