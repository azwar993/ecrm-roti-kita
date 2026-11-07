<?php

use App\Http\Controllers\Admin\ComboController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\RevenueReportController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CatalogController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\FeedbackController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public storefront
|--------------------------------------------------------------------------
*/

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/menu', [StorefrontController::class, 'menu'])->name('storefront.menu');
Route::get('/promo', [StorefrontController::class, 'promotions'])->name('storefront.promotions');
Route::get('/tentang-kami', [StorefrontController::class, 'about'])->name('storefront.about');
Route::get('/artikel', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/artikel/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/menu/produk/{product}', [StorefrontController::class, 'product'])
    ->whereNumber('product')->name('storefront.product');

Route::get('/dashboard', function () {
    return auth()->user()->role === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('customer.dashboard');
})->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Session cart — intentionally public
|--------------------------------------------------------------------------
| Guests can add and edit items in their session cart. Checkout remains
| protected by checkout.auth + role:customer further below. Laravel session data is
| retained when the session ID is regenerated at login/registration.
*/

Route::get('/customer/cart', [CartController::class, 'index'])->name('customer.cart');
Route::post('/customer/cart/items', [CartController::class, 'store'])->name('customer.cart.items.store');
Route::put('/customer/cart/items/{type}/{id}', [CartController::class, 'update'])
    ->where('type', 'product|combo')
    ->whereNumber('id')
    ->name('customer.cart.items.update');
Route::delete('/customer/cart/items/{type}/{id}', [CartController::class, 'destroy'])
    ->where('type', 'product|combo')
    ->whereNumber('id')
    ->name('customer.cart.items.destroy');

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/reports/revenue', RevenueReportController::class)->name('reports.revenue');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/payment-settings', [PaymentSettingController::class, 'edit'])->name('payment-settings.edit');
    Route::put('/payment-settings', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
    Route::post('/inbox/{notification}/open', [InboxController::class, 'open'])->name('inbox.open');
    Route::patch('/inbox/read-all', [InboxController::class, 'markAllRead'])->name('inbox.read-all');
    Route::get('/orders/{order}/payment-proof', [InboxController::class, 'proof'])->whereNumber('order')->name('orders.payment-proof');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
    Route::resource('/articles', App\Http\Controllers\Admin\ArticleController::class)->except(['show'])->parameters(['articles' => 'article']);
    Route::get('/tickets', [App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->middleware('throttle:10,1')->whereNumber('ticket')->name('tickets.reply');
    Route::patch('/tickets/{ticket}', [App\Http\Controllers\Admin\SupportTicketController::class, 'update'])->whereNumber('ticket')->name('tickets.update');
    Route::get('/ticket-attachments/{attachment}', [App\Http\Controllers\Customer\SupportTicketController::class, 'download'])->whereNumber('attachment')->name('tickets.attachments.download');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product')->name('products.show');
    Route::patch('/products/{product}/active', [ProductController::class, 'toggleActive'])
        ->whereNumber('product')->name('products.active');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
        ->whereNumber('product')->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->whereNumber('product')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->whereNumber('product')->name('products.destroy');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
        ->whereNumber('order')->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
        ->whereNumber('order')->name('orders.status');
    Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'verifyPayment'])
        ->whereNumber('order')->name('orders.payment');
    Route::get('/orders/{order}/receipt', [AdminOrderController::class, 'receipt'])
        ->whereNumber('order')->name('orders.receipt');

    Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions');
    Route::get('/promotions/create', [PromotionController::class, 'create'])->name('promotions.create');
    Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
    Route::get('/promotions/{promotion}/edit', [PromotionController::class, 'edit'])->whereNumber('promotion')->name('promotions.edit');
    Route::put('/promotions/{promotion}', [PromotionController::class, 'update'])->whereNumber('promotion')->name('promotions.update');
    Route::patch('/promotions/{promotion}/active', [PromotionController::class, 'toggleActive'])->whereNumber('promotion')->name('promotions.active');
    Route::delete('/promotions/{promotion}', [PromotionController::class, 'destroy'])->whereNumber('promotion')->name('promotions.destroy');

    Route::resource('/combos', ComboController::class)->except(['show'])->parameters(['combos' => 'combo']);
    Route::patch('/combos/{combo}/active', [ComboController::class, 'toggleActive'])->whereNumber('combo')->name('combos.active');

    Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('feedback');
    Route::patch('/feedback/{feedback}', [AdminFeedbackController::class, 'update'])->whereNumber('feedback')->name('feedback.update');
});

/*
|--------------------------------------------------------------------------
| Customer routes
|--------------------------------------------------------------------------
*/

Route::prefix('customer')->name('customer.')->middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/dashboard', CustomerDashboardController::class)->name('dashboard');
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');

    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])
        ->whereNumber('order')->name('orders.show');
    Route::get('/orders/{order}/receipt', [CustomerOrderController::class, 'receipt'])
        ->whereNumber('order')->name('orders.receipt');
    Route::post('/orders/{order}/payment-proof', [CustomerOrderController::class, 'uploadPaymentProof'])->whereNumber('order')->name('orders.payment-proof.store');
    Route::get('/orders/{order}/payment-proof', [CustomerOrderController::class, 'paymentProof'])->whereNumber('order')->name('orders.payment-proof');

    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/tickets', [App\Http\Controllers\Customer\SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [App\Http\Controllers\Customer\SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [App\Http\Controllers\Customer\SupportTicketController::class, 'store'])->middleware('throttle:5,1')->name('tickets.store');
    Route::get('/tickets/{ticket}', [App\Http\Controllers\Customer\SupportTicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [App\Http\Controllers\Customer\SupportTicketController::class, 'reply'])->middleware('throttle:10,1')->whereNumber('ticket')->name('tickets.reply');
    Route::get('/ticket-attachments/{attachment}', [App\Http\Controllers\Customer\SupportTicketController::class, 'download'])->whereNumber('attachment')->name('tickets.attachments.download');
});

Route::prefix('customer')->name('customer.')->middleware(['checkout.auth', 'role:customer'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout/quote', [CheckoutController::class, 'quotePromotion'])->name('checkout.quote');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
});
