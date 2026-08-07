<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\Manager\TicketController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public customer authorization signature flow
Route::get('/booking/authorize/{booking}/{hash}', [CustomerAuthController::class, 'show'])->name('customer.authorize');
Route::post('/booking/authorize/{booking}/{hash}', [CustomerAuthController::class, 'approve'])->name('customer.authorize.approve');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/bookings/{booking}/remarks', [BookingController::class, 'addRemark'])->name('bookings.add-remark');
    Route::post('/bookings/{booking}/update-tickets', [BookingController::class, 'updateTicketsAndSeats'])->name('bookings.update-tickets');
    
    // Auth Email Generation & Preview Routes
    Route::get('/bookings/{booking}/auth-email/preview', [BookingController::class, 'previewAuthEmail'])->name('bookings.auth-email.preview');
    Route::post('/bookings/{booking}/auth-email/send', [BookingController::class, 'sendAuthEmail'])->name('bookings.auth-email.send');
    
    // Assign to Ticketing Team Route
    Route::post('/bookings/{booking}/assign-ticketing', [TicketController::class, 'assignTicketingUser'])->name('bookings.assign-ticketing');

    // Call Logs Routes
    Route::get('/call-logs', [\App\Http\Controllers\CallLogController::class, 'index'])->name('call-logs.index');
    Route::post('/call-logs', [\App\Http\Controllers\CallLogController::class, 'store'])->name('call-logs.store');
    Route::get('/call-logs/export', [\App\Http\Controllers\CallLogController::class, 'exportCsv'])->name('call-logs.export');
    Route::get('/call-logs/{callLog}', [\App\Http\Controllers\CallLogController::class, 'show'])->name('call-logs.show');
    Route::delete('/call-logs/{callLog}', [\App\Http\Controllers\CallLogController::class, 'destroy'])->name('call-logs.destroy');

    // Change Request Submission Routes
    Route::get('/bookings/{booking}/request-change', [\App\Http\Controllers\ChangeRequestController::class, 'create'])->name('bookings.request-change.create');
    Route::post('/bookings/{booking}/request-change', [\App\Http\Controllers\ChangeRequestController::class, 'store'])->name('bookings.request-change');
});

Route::middleware(['auth', 'role:manager|admin|changes'])->group(function () {
    Route::get('/changes/requests', [\App\Http\Controllers\ChangeRequestController::class, 'index'])->name('changes.index');
    Route::post('/changes/requests/{changeRequest}/status', [\App\Http\Controllers\ChangeRequestController::class, 'updateStatus'])->name('changes.update-status');
});

Route::middleware(['auth', 'role:manager|admin|ticketing'])->group(function () {
    Route::post('/bookings/{booking}/approve-auth', [BookingController::class, 'approveAuth'])->name('bookings.approve-auth');
    Route::get('/manager/tickets', [TicketController::class, 'index'])->name('manager.tickets.index');
    Route::post('/manager/tickets/{booking}/approve-payment', [TicketController::class, 'approvePayment'])->name('manager.tickets.approve-payment');
    Route::get('/manager/tickets/{booking}/preview', [TicketController::class, 'previewETicket'])->name('manager.tickets.preview');
    Route::get('/manager/tickets/{booking}/preview-email', [TicketController::class, 'previewETicketEmail'])->name('manager.tickets.preview-email');
    Route::post('/manager/tickets/{booking}/update-ticket-details', [TicketController::class, 'updateTicketDetails'])->name('manager.tickets.update-ticket-details');
    Route::post('/manager/tickets/{booking}/send', [TicketController::class, 'sendETicket'])->name('manager.tickets.send');
});

// Admin Panel Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Merchant Management
    Route::get('/merchants', [MerchantController::class, 'index'])->name('merchants.index');
    Route::get('/merchants/create', [MerchantController::class, 'create'])->name('merchants.create');
    Route::post('/merchants', [MerchantController::class, 'store'])->name('merchants.store');
    Route::get('/merchants/{merchant}/edit', [MerchantController::class, 'edit'])->name('merchants.edit');
    Route::put('/merchants/{merchant}', [MerchantController::class, 'update'])->name('merchants.update');
    Route::post('/merchants/{merchant}/toggle-active', [MerchantController::class, 'toggleActive'])->name('merchants.toggle-active');
    Route::post('/merchants/{merchant}/test-smtp', [MerchantController::class, 'testSmtp'])->name('merchants.test-smtp');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    // Admin Booking Management & CSV Export
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/export', [ExportController::class, 'export'])->name('bookings.export');
    Route::get('/bookings/{booking}/edit', [AdminBookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
    Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');
    Route::post('/bookings/{booking}/add-admin-remark', [AdminBookingController::class, 'addAdminRemark'])->name('bookings.add-admin-remark');
    Route::post('/bookings/{booking}/update-case-status', [AdminBookingController::class, 'updateCaseStatus'])->name('bookings.update-case-status');
});
