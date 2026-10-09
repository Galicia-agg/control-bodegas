<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RoleController;
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

    Route::get('clientes', [CustomerController::class, 'index'])->middleware('can:customers.view')->name('customers.index');
    Route::middleware('can:customers.manage')->group(function () {
        Route::post('clientes', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('clientes/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('clientes/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    Route::get('ingresos', [ReceiptController::class, 'index'])->middleware('can:receipts.view')->name('receipts.index');
    Route::get('ingresos/nuevo', [ReceiptController::class, 'create'])->middleware('can:receipts.create')->name('receipts.create');
    Route::post('ingresos', [ReceiptController::class, 'store'])->middleware('can:receipts.create')->name('receipts.store');
    Route::get('ingresos/{receipt}', [ReceiptController::class, 'show'])->middleware('can:receipts.view')->name('receipts.show');

    Route::get('tarifas', [RateController::class, 'index'])->middleware('can:rates.view')->name('rates.index');
    Route::middleware('can:rates.manage')->group(function () {
        Route::post('tarifas', [RateController::class, 'store'])->name('rates.store');
        Route::put('tarifas/{rate}', [RateController::class, 'update'])->name('rates.update');
        Route::delete('tarifas/{rate}', [RateController::class, 'destroy'])->name('rates.destroy');
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
