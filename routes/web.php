<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardsController;
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
    Route::get('/products', [DashboardsController::class, 'products']);
    Route::get('/product-details', [DashboardsController::class, 'product_details']);
    Route::get('/cart', [DashboardsController::class, 'cart']);
    Route::get('/checkout', [DashboardsController::class, 'checkout']);
    Route::get('/customers-list', [DashboardsController::class, 'customers_list']);
    Route::view('/customers', 'customers')->name('customers');
    Route::get('/orders', [DashboardsController::class, 'orders']);
    Route::get('/orders-details', [DashboardsController::class, 'orders_details']);
    Route::get('/add-product', [DashboardsController::class, 'add_product']);
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
        'tenant_id' => auth()->user()->tenant->id,
        'task' => $task,
        'all_tasks' => Task::all(),
    ]);
});

require __DIR__.'/auth.php';
