<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\DailyReportController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\Manager\TicketController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

use App\Http\Controllers\Admin\PaymentChargeController;
use App\Http\Controllers\PublicPaymentController;
use App\Http\Controllers\MerchantChargeController;

// Dedicated Merchant Charge Terminal Routes (Standalone Portal)
Route::get('/merchentcharge', [MerchantChargeController::class, 'index'])->name('merchant.charge.index');
Route::get('/merchantcharge', [MerchantChargeController::class, 'index']);
Route::post('/merchentcharge/login', [MerchantChargeController::class, 'login'])->name('merchant.charge.login');
Route::post('/merchantcharge/login', [MerchantChargeController::class, 'login']);
Route::post('/merchentcharge/logout', [MerchantChargeController::class, 'logout'])->name('merchant.charge.logout');
Route::post('/merchantcharge/logout', [MerchantChargeController::class, 'logout']);

Route::middleware('auth')->group(function () {
    Route::get('/merchentcharge/search-bookings', [MerchantChargeController::class, 'searchBookings'])->name('merchant.charge.search');
    Route::get('/merchantcharge/search-bookings', [MerchantChargeController::class, 'searchBookings']);
    Route::get('/merchentcharge/booking/{booking}', [MerchantChargeController::class, 'getBooking'])->name('merchant.charge.booking');
    Route::get('/merchantcharge/booking/{booking}', [MerchantChargeController::class, 'getBooking']);
    Route::post('/merchentcharge/process', [MerchantChargeController::class, 'charge'])->name('merchant.charge.process');
    Route::post('/merchantcharge/process', [MerchantChargeController::class, 'charge']);
    Route::get('/merchentcharge/transactions', [MerchantChargeController::class, 'transactions'])->name('merchant.charge.transactions');
    Route::get('/merchantcharge/transactions', [MerchantChargeController::class, 'transactions']);
});

// Public customer authorization signature flow
Route::get('/booking/authorize/{booking}/{hash}', [CustomerAuthController::class, 'show'])->name('customer.authorize');
Route::post('/booking/authorize/{booking}/{hash}', [CustomerAuthController::class, 'approve'])->name('customer.authorize.approve');

// Public customer payment link checkout flow
Route::get('/pay/{token}', [PublicPaymentController::class, 'show'])->name('payment.show');
Route::post('/pay/{token}', [PublicPaymentController::class, 'process'])->name('payment.process');

Route::middleware('auth')->group(function () {
    Route::post('/pnr/parse', [\App\Http\Controllers\Api\PnrController::class, 'parse'])->name('pnr.parse');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::get('/bookings/{booking}/json', [BookingController::class, 'getBookingJson'])->name('bookings.json');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/bookings/{booking}/remarks', [BookingController::class, 'addRemark'])->name('bookings.add-remark');
    Route::post('/bookings/{booking}/update-tickets', [BookingController::class, 'updateTicketsAndSeats'])->name('bookings.update-tickets');
    Route::post('/bookings/{booking}/update-status', [BookingController::class, 'updateStatus'])->name('bookings.update-status');
    
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
    Route::get('/changes/queue', [\App\Http\Controllers\ChangeRequestController::class, 'index'])->name('changes.queue');
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

// Booking Edit & Update (Accessible to Admin, Manager, Ticketing, and Agent)
Route::middleware(['auth', 'role:admin|manager|ticketing|agent'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/bookings/{booking}/edit', [AdminBookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
});

// Admin Panel Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
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
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Admin Booking Management & CSV Export
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/export', [ExportController::class, 'export'])->name('bookings.export');
    Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');
    Route::post('/bookings/{booking}/add-admin-remark', [AdminBookingController::class, 'addAdminRemark'])->name('bookings.add-admin-remark');
    Route::post('/bookings/{booking}/update-case-status', [AdminBookingController::class, 'updateCaseStatus'])->name('bookings.update-case-status');

    // Merchant Payment Charges & Link Management
    Route::get('/charges', [PaymentChargeController::class, 'index'])->name('charges.index');
    Route::post('/charges/link', [PaymentChargeController::class, 'storeLink'])->name('charges.store-link');
    Route::post('/charges/link/{paymentLink}/cancel', [PaymentChargeController::class, 'cancelLink'])->name('charges.cancel-link');
    Route::post('/charges/direct', [PaymentChargeController::class, 'directCharge'])->name('charges.direct');
    Route::get('/charges/find-booking', [PaymentChargeController::class, 'findBookingByPnr'])->name('charges.find-booking');
    // Daily Reports
    Route::get('/reports/daily', [DailyReportController::class, 'index'])->name('reports.daily');
    Route::get('/reports/daily/detail', [DailyReportController::class, 'detail'])->name('reports.daily.detail');
    Route::get('/reports/daily/export', [DailyReportController::class, 'exportCsv'])->name('reports.daily.export');
});

// HR & Accounts Exclusive Payroll Management Routes
use App\Http\Controllers\Payroll\EmployeeProfileController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\Payroll\LeaveManagementController;
use App\Http\Controllers\Employee\MyPayslipController;

Route::middleware(['auth', 'role:hr|accounts'])->prefix('payroll')->name('payroll.')->group(function () {
    // Employee Profile & Salary Management
    Route::get('/employees', [EmployeeProfileController::class, 'index'])->name('employees.index');
    Route::get('/employees/{user}/edit', [EmployeeProfileController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{user}', [EmployeeProfileController::class, 'update'])->name('employees.update');
    Route::get('/employees/{user}/salary-data', [EmployeeProfileController::class, 'getSalaryData'])->name('employees.salary-data');

    // Monthly Payslips
    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/create', [PayslipController::class, 'create'])->name('payslips.create');
    Route::post('/payslips/preview', [PayslipController::class, 'calculatePreview'])->name('payslips.preview');
    Route::post('/payslips', [PayslipController::class, 'store'])->name('payslips.store');
    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
    Route::get('/payslips/{payslip}/pdf', [PayslipController::class, 'downloadPdf'])->name('payslips.download-pdf');
    Route::delete('/payslips/{payslip}', [PayslipController::class, 'destroy'])->name('payslips.destroy');

    // Leave Balances & Accrual
    Route::get('/leaves', [LeaveManagementController::class, 'index'])->name('leaves.index');
    Route::put('/leaves/{user}', [LeaveManagementController::class, 'update'])->name('leaves.update');
    Route::post('/leaves/accrue', [LeaveManagementController::class, 'triggerAccrual'])->name('leaves.accrue');
});

// Employee Portal: Self-service view and download password-protected payslips
Route::middleware('auth')->group(function () {
    Route::get('/my-payslips', [MyPayslipController::class, 'index'])->name('employee.payslips.index');
    Route::get('/my-payslips/{payslip}/pdf', [MyPayslipController::class, 'downloadPdf'])->name('employee.payslips.download-pdf');
});

