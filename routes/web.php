<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('bodegas', [WarehouseController::class, 'index'])->middleware('can:warehouses.view')->name('warehouses.index');
    Route::middleware('can:warehouses.manage')->group(function () {
        Route::post('bodegas', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::put('bodegas/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('bodegas/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });

    Route::get('productos', [ProductController::class, 'index'])->middleware('can:products.view')->name('products.index');
    Route::middleware('can:products.manage')->group(function () {
        Route::post('productos', [ProductController::class, 'store'])->name('products.store');
        Route::put('productos/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('productos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::get('categorias', [CategoryController::class, 'index'])->middleware('can:products.view')->name('categories.index');
    Route::middleware('can:products.manage')->group(function () {
        Route::post('categorias', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::get('proveedores', [SupplierController::class, 'index'])->middleware('can:suppliers.view')->name('suppliers.index');
    Route::middleware('can:suppliers.manage')->group(function () {
        Route::post('proveedores', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('proveedores/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('proveedores/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    Route::get('inventario', StockController::class)->middleware('can:stock.view')->name('stock.index');

    Route::middleware('can:movements.view')->group(function () {
        Route::get('movimientos', [MovementController::class, 'index'])->name('movements.index');
        Route::get('movimientos/nuevo', [MovementController::class, 'create'])->name('movements.create');
        Route::post('movimientos', [MovementController::class, 'store'])->name('movements.store');
        Route::get('movimientos/{movement}', [MovementController::class, 'show'])->name('movements.show');
    });

    Route::middleware('can:users.manage')->group(function () {
        Route::get('usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('usuarios', [UserController::class, 'store'])->name('users.store');
        Route::put('usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('can:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
});

require __DIR__.'/settings.php';
