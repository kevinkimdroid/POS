<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/template', [ProductController::class, 'template'])->name('inventory.template');
    Route::post('/inventory/import', [ProductController::class, 'import'])->name('inventory.import');
    Route::get('/inventory/create', [ProductController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::get('/checkout/search', [CheckoutController::class, 'search'])->name('checkout.search');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/returns', [SaleController::class, 'returns'])->name('returns.index');
    Route::post('/sales/{sale}/refund', [SaleController::class, 'refund'])->name('sales.refund');
    Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
    Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('/deliveries', [SaleController::class, 'deliveries'])->name('deliveries.index');
    Route::post('/deliveries/{note}', [SaleController::class, 'dispatchNote'])->name('deliveries.update');

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('/stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
    Route::post('/stock/transfer', [StockController::class, 'transfer'])->name('stock.transfer');
    Route::post('/stock/counts', [StockController::class, 'startCount'])->name('stock.counts.store');
    Route::get('/stock/counts/{stockTake}', [StockController::class, 'count'])->name('stock.count');
    Route::post('/stock/counts/{stockTake}', [StockController::class, 'postCount'])->name('stock.counts.post');
    Route::get('/inventory/{product}/label', [SaleController::class, 'label'])->name('inventory.label');

    Route::get('/purchasing', [PurchaseController::class, 'index'])->name('purchasing.index');
    Route::post('/purchasing/suppliers', [PurchaseController::class, 'storeSupplier'])->name('purchasing.suppliers.store');
    Route::post('/purchasing/orders', [PurchaseController::class, 'storeOrder'])->name('purchasing.orders.store');
    Route::post('/purchasing/orders/{order}/receive', [PurchaseController::class, 'receive'])->name('purchasing.receive');
    Route::post('/purchasing/payments', [PurchaseController::class, 'pay'])->name('purchasing.pay');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/payments', [CustomerController::class, 'collect'])->name('customers.collect');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/shift', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shift/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shift/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team', [TeamController::class, 'store'])->name('team.store');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
