@extends('merchant_charge.layout')

@section('title', 'Merchant Charge Terminal - Direct Gateway')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3" x-data="merchantChargeApp()" x-init="initApp()" x-cloak>

    <!-- Top Terminal Navigation Bar -->
    <header class="terminal-header rounded-3 p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 software-card">
        <div class="d-flex align-items-center gap-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 p-2 text-primary shadow-sm">
                <i class="bi bi-credit-card-2-front-fill fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold text-dark mb-0">Merchant Charge Terminal</h5>
                    <span class="badge bg-success text-light border border-success  badge-terminal d-inline-flex align-items-center">
                        <span class="status-dot active"></span> NMI GATEWAY LIVE
                    </span>
                </div>
                <div class="text-secondary small font-mono" style="font-size: 0.75rem;">
                    PCI-DSS Level 1 Direct Point-of-Sale Portal
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Active Clock -->
            <div class="d-none d-md-flex align-items-center gap-2 bg-light px-3 py-1.5 rounded-3 border text-dark font-mono small">
                <i class="bi bi-clock-history text-dark"></i>
                <span x-text="currentTime"></span>
            </div>

            <!-- Operator Profile -->
            <div class="d-flex align-items-center gap-2 bg-light px-3 py-1.5 rounded-3 border">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold small" style="width: 28px; height: 28px; font-size: 0.75rem;">
                    {{ strtoupper(substr(Auth::user()->name ?? 'OP', 0, 2)) }}
                </div>
                <div class="d-none d-sm-block text-start">
                    <div class="text-dark small fw-bold lh-1">{{ Auth::user()->name }}</div>
                    <div class="text-muted font-mono" style="font-size: 0.7rem;">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <!-- Logout -->
            <form action="{{ route('merchant.charge.logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm border-danger border-opacity-30 d-flex align-items-center gap-1.5 py-1.5 px-2.5 rounded-3" title="Exit Terminal">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-sm-inline small fw-semibold">Logout</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Terminal Container -->
    <div class="row g-4">

        <!-- ========================================== -->
        <!-- STEP 1: SELECT MERCHANT (MANDATORY STEP 1) -->
        <!-- ========================================== -->
        <div class="col-12">
            <div class="software-card p-3 p-md-4 bg-white border">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white font-mono px-2.5 py-1 rounded-pill">STEP 1</span>
                        <h5 class="fw-bold text-dark mb-0">Select Target Merchant Gateway</h5>
                        <span class="text-secondary small d-none d-md-inline">• Choose which merchant account to process the transaction</span>
                    </div>
                    <div class="text-secondary small font-mono d-flex align-items-center gap-2">
                        <div>
                            <span class="text-muted">Active Gateway:</span> 
                            <strong class="text-primary fs-6" x-text="selectedMerchant ? selectedMerchant.name : 'None Selected'"></strong>
                        </div>
                        <template x-if="selectedMerchant && selectedMerchant.wallet_balance_num > 0">
                            <span class="badge bg-light text-dark border ms-1">
                                Wallet Balance: <strong class="text-primary" x-text="'$' + selectedMerchant.wallet_balance_formatted"></strong>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Merchant Cards Grid -->
                <div class="row g-3">
                    <template x-for="merchant in merchants" :key="merchant.id">
                        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                            <div class="p-3 merchant-select-card h-100 d-flex flex-column justify-content-between"
                                 :class="{ 'selected': selectedMerchantId === merchant.id }"
                                 @click="selectMerchant(merchant)">
                                
                                <div>
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                                <span x-text="merchant.name"></span>
                                                <template x-if="merchant.is_active">
                                                    <span class="badge bg-success text-white" style="font-size: 0.65rem;">Active</span>
                                                </template>
                                                <template x-if="!merchant.is_active">
                                                    <span class="badge bg-danger text-white" style="font-size: 0.65rem;">Inactive</span>
                                                </template>
                                            </div>
                                            <div class="text-secondary font-mono small" style="font-size: 0.75rem;">
                                                Code: <span class="text-dark fw-bold" x-text="merchant.merchant_code || merchant.code || 'N/A'"></span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark font-mono border" x-text="merchant.currency || 'USD'">
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Merchant Wallet Balance Box -->
                                    <div class="p-2.5 rounded bg-light border border-light-subtle mb-2">
                                        <div class="d-flex align-items-center justify-content-between font-mono small mb-1" style="font-size: 0.75rem;">
                                            <span class="text-secondary fw-semibold">Wallet Balance:</span>
                                            <span class="fw-bold text-primary" 
                                                  x-text="merchant.wallet_balance_num > 0 ? ('$' + merchant.wallet_balance_formatted) : 'Unlimited / Not Set'"></span>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between font-mono small" style="font-size: 0.72rem;">
                                            <span class="text-muted">Total Processed:</span>
                                            <span class="text-dark fw-semibold" x-text="'$' + (merchant.total_charged_formatted || '0.00')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle font-mono" style="font-size: 0.75rem;">
                                    <span class="text-secondary d-flex align-items-center gap-1">
                                        <i class="bi bi-shield-check text-success"></i> NMI Gateway
                                    </span>
                                    <span class="text-primary fw-bold" x-show="selectedMerchantId === merchant.id">
                                        <i class="bi bi-check-circle-fill me-1"></i>Selected
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="col-12" x-show="merchants.length === 0">
                        <div class="alert alert-warning mb-0 bg-warning bg-opacity-10 border border-warning border-opacity-25 text-dark">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>No merchants configured in the database yet. Please configure a merchant in Admin &gt; Merchants.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- LEFT COLUMN: BOOKING LOOKUP & DETAILS      -->
        <!-- ========================================== -->
        <div class="col-12 col-lg-6 col-xl-7">

            <!-- STEP 2: BOOKING LOOKUP -->
            <div class="software-card p-3 p-md-4 mb-4 bg-white border">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white font-mono px-2.5 py-1 rounded-pill">STEP 2</span>
                        <h6 class="fw-bold text-dark mb-0">Booking Database Lookup &amp; Customer Autofill</h6>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" 
                                @click="clearSelectedBooking()" 
                                x-show="selectedBooking" 
                                class="btn btn-outline-secondary btn-sm py-0.5 px-2 font-mono" 
                                style="font-size: 0.75rem;">
                            <i class="bi bi-x-circle me-1"></i>Direct Charge Mode
                        </button>
                    </div>
                </div>

                <!-- Instant Search Input -->
                <div class="position-relative mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               x-model="searchQuery" 
                               @input.debounce.300ms="performBookingSearch()"
                               @focus="isSearching = true"
                               class="form-control" 
                               placeholder="Type Airline PNR, GK PNR, Booking ID, Customer Name, Phone, or Card Holder...">
                        <button class="btn btn-outline-secondary" 
                                type="button" 
                                x-show="searchQuery" 
                                @click="searchQuery = ''; performBookingSearch()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Search Dropdown Results -->
                    <div class="position-absolute w-100 mt-1 shadow-lg rounded-3 overflow-hidden border bg-white z-3"
                         x-show="searchResults.length > 0 && searchQuery.length > 0"
                         @click.away="searchResults = []"
                         style="max-height: 280px; overflow-y: auto;">
                        <div class="p-2 bg-light border-bottom text-secondary small font-mono d-flex justify-content-between">
                            <span>Matching Booking Records:</span>
                            <span class="text-dark fw-bold" x-text="searchResults.length + ' found'"></span>
                        </div>
                        <template x-for="item in searchResults" :key="item.id">
                            <div class="p-3 border-bottom hover-bg-light cursor-pointer transition-colors"
                                 @click="selectBooking(item)"
                                 style="cursor: pointer;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white font-mono" x-text="'#' + item.booking_id"></span>
                                        <span class="fw-bold text-dark" x-text="item.customer_name"></span>
                                        <span class="text-secondary small font-mono" x-text="'(PNR: ' + item.airline_pnr + ')'"></span>
                                    </div>
                                    <div class="text-end font-mono fw-bold text-success">
                                        <span x-text="'$' + Number(item.total_amount).toFixed(2)"></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between small text-secondary font-mono" style="font-size: 0.75rem;">
                                    <span>
                                        <i class="bi bi-airplane me-1 text-primary"></i><span class="text-dark" x-text="item.airline_name"></span> 
                                        <span x-show="item.phone" class="ms-2"><i class="bi bi-telephone me-1 text-secondary"></i><span class="text-dark" x-text="item.phone"></span></span>
                                    </span>
                                    <span class="badge bg-light text-dark border" x-text="item.payment_status || 'pending'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Selected Booking Overview & Payment Info Banner -->
                <template x-if="selectedBooking">
                    <div class="p-3 rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-check-fill text-primary fs-5"></i>
                                <h6 class="fw-bold text-dark mb-0">
                                    Selected Booking: <span class="font-mono text-primary" x-text="'#' + selectedBooking.booking_id"></span>
                                </h6>
                            </div>
                            <span class="badge bg-primary bg-opacity-15 text-light border border-primary border-opacity-25 font-mono" 
                                  x-text="'PNR: ' + (selectedBooking.airline_pnr || selectedBooking.gk_pnr || 'N/A')">
                            </span>
                        </div>

                        <div class="row g-2 small font-mono text-secondary mb-3">
                            <div class="col-6 col-md-4">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">CUSTOMER</span>
                                <strong class="text-dark" x-text="customerFirstName + ' ' + customerLastName"></strong>
                            </div>
                            <div class="col-6 col-md-4">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">BOOKING TOTAL</span>
                                <strong class="text-success fs-6" x-text="'$' + Number(chargeAmount).toFixed(2)"></strong>
                            </div>
                            <div class="col-6 col-md-4">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">AIRLINE / STATUS</span>
                                <span class="text-dark" x-text="(selectedBooking.airline_name || 'Airline') + ' (' + (selectedBooking.booking_status || 'N/A') + ')'"></span>
                            </div>
                        </div>

                        <!-- Stored Card Info Badge if Present in Booking -->
                        <div x-show="selectedBooking.card_last_4 || selectedBooking.card_holder_name" 
                             class="p-2 rounded-2 bg-white border border-secondary-subtle mb-3 d-flex align-items-center justify-content-between shadow-sm">
                            <div class="d-flex align-items-center gap-2 small font-mono">
                                <i class="bi bi-credit-card text-warning fs-6"></i>
                                <span class="text-secondary">Saved Card in Booking:</span>
                                <span class="badge bg-light text-dark border" x-text="(selectedBooking.card_type || 'Card') + ' •••• ' + (selectedBooking.card_last_4 || '----')"></span>
                                <span class="text-muted" x-show="selectedBooking.card_expiration" x-text="'Exp: ' + selectedBooking.card_expiration"></span>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20" style="font-size: 0.7rem;">From CRM</span>
                        </div>

                        <!-- ========================================== -->
                        <!-- STEP 3: PAYMENT INFO (RENAMED FROM REMARKS)-->
                        <!-- ========================================== -->
                        <div class="p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-50">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label text-dark fw-bold small mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-warning"></i>
                                    <span>Payment info</span>
                                </label>
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 font-mono" style="font-size: 0.7rem;">
                                    Booking Payment Record
                                </span>
                            </div>

                            <!-- Display Existing Payment Info -->
                            <div class="p-2.5 rounded bg-white border border-warning border-opacity-50 font-mono small text-dark mb-2 shadow-sm"
                                 style="white-space: pre-wrap; max-height: 110px; overflow-y: auto;"
                                 x-text="selectedBooking.payment_info || 'No existing payment info notes recorded on this booking.'">
                            </div>

                            <!-- Editable Note for this specific charge -->
                            <div class="mt-2">
                                <label class="form-label text-dark small mb-1 fw-semibold" style="font-size: 0.75rem;">
                                    Append Charge Remark / Info Note:
                                </label>
                                <input type="text" 
                                       x-model="paymentInfoNote" 
                                       class="form-control font-mono text-sm py-1.5 border-warning" 
                                       placeholder="e.g. Authorized by customer over phone, ref code #994">
                            </div>
                        </div>

                    </div>
                </template>

                <!-- Customer Billing Details Form -->
                <div class="border-top border-light-subtle pt-3">
                    <h6 class="fw-bold text-dark small mb-3 text-uppercase tracking-wider">Customer Billing Information</h6>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-dark">First Name</label>
                            <input type="text" x-model="customerFirstName" class="form-control" placeholder="John">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-dark">Last Name</label>
                            <input type="text" x-model="customerLastName" class="form-control" placeholder="Doe">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-dark">Phone Number</label>
                            <input type="text" x-model="customerPhone" class="form-control font-mono" placeholder="+1 555 019 2834">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-dark">Email Address</label>
                            <input type="email" x-model="customerEmail" class="form-control" placeholder="customer@example.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-dark">Billing Street Address</label>
                            <input type="text" x-model="billingAddress1" class="form-control" placeholder="123 Main Street, Apt 4B">
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-dark">City</label>
                            <input type="text" x-model="billingCity" class="form-control" placeholder="New York">
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-dark">State / Province</label>
                            <input type="text" x-model="billingState" class="form-control" placeholder="NY">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label text-dark">ZIP / Postal Code</label>
                            <input type="text" x-model="billingZip" class="form-control font-mono" placeholder="10001">
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================== -->
        <!-- RIGHT COLUMN: VIRTUAL CARD & CHARGE FORM   -->
        <!-- ========================================== -->
        <div class="col-12 col-lg-6 col-xl-5">

            <!-- STEP 4: CARD DETAILS & LIVE VISUALIZER -->
            <div class="software-card p-3 p-md-4 mb-4 bg-white border position-sticky" style="top: 20px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success text-white font-mono px-2.5 py-1 rounded-pill">STEP 4</span>
                        <h6 class="fw-bold text-dark mb-0">Card Details &amp; Processing</h6>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 font-mono small">
                        <i class="bi bi-shield-lock-fill text-success me-1"></i>NMI Direct
                    </span>
                </div>

                <!-- 3D Styled Virtual Credit Card Mockup -->
                <div class="credit-card-preview mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="chip"></div>
                        <div class="text-end">
                            <span class="fw-bold fs-5 tracking-widest font-mono text-uppercase" 
                                  :class="{
                                      'text-info': detectedCardBrand === 'visa',
                                      'text-warning': detectedCardBrand === 'mastercard',
                                      'text-primary': detectedCardBrand === 'amex',
                                      'text-danger': detectedCardBrand === 'discover',
                                      'text-white': !detectedCardBrand
                                  }"
                                  x-text="detectedCardBrand ? detectedCardBrand.toUpperCase() : 'CARD'">
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="font-mono fs-4 text-white tracking-widest" style="letter-spacing: 0.15em;"
                             x-text="formattedCardNumber || '•••• •••• •••• ••••'">
                        </div>
                    </div>

                    <div class="d-flex align-items-end justify-content-between font-mono">
                        <div>
                            <div class="text-secondary small" style="font-size: 0.65rem; color: #94a3b8 !important;">CARDHOLDER</div>
                            <div class="text-white small fw-bold text-uppercase" 
                                 x-text="(customerFirstName || customerLastName) ? (customerFirstName + ' ' + customerLastName) : 'CARD HOLDER NAME'">
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-secondary small" style="font-size: 0.65rem; color: #94a3b8 !important;">EXPIRES</div>
                            <div class="text-white small fw-bold" x-text="formattedExpiry || 'MM/YY'"></div>
                        </div>
                    </div>
                </div>

                <!-- Card Input Form Fields -->
                <form @submit.prevent="processDirectCharge()">
                    
                    <!-- Charge Amount -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-bold d-flex justify-content-between mb-1">
                            <span>Charge Amount (<span x-text="selectedMerchant ? selectedMerchant.currency : 'USD'"></span>)</span>
                            <span class="text-success font-mono fw-bold fs-6" x-text="'$' + (Number(chargeAmount) || 0).toFixed(2)"></span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text text-success font-mono fw-bold fs-5">$</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   x-model="chargeAmount" 
                                   class="form-control font-mono fs-5 fw-bold text-success" 
                                   placeholder="0.00" 
                                   required>
                        </div>
                        <!-- Live Merchant Wallet Balance Info -->
                        <div class="mt-1 font-mono small d-flex align-items-center justify-content-between" x-show="selectedMerchant && selectedMerchant.wallet_balance_num > 0" style="font-size: 0.73rem;">
                            <span class="text-secondary">Gateway Wallet Balance:</span>
                            <span class="text-primary fw-bold" x-text="'$' + selectedMerchant.wallet_balance_formatted"></span>
                        </div>
                    </div>

                    <!-- Card Number Input -->
                    <div class="mb-3">
                        <label class="form-label text-dark">Card Number</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-credit-card-2-front"></i>
                            </span>
                            <input type="text" 
                                   x-model="cardNumber" 
                                   @input="formatCardNumberInput($event)"
                                   maxlength="19"
                                   class="form-control font-mono" 
                                   placeholder="4000 0000 0000 0000" 
                                   required>
                        </div>
                    </div>

                    <!-- Expiration & CVV -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-dark">Expiry (MM/YY)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                                <input type="text" 
                                       x-model="cardExpiry" 
                                       @input="formatExpiryInput($event)"
                                       maxlength="5"
                                       class="form-control font-mono" 
                                       placeholder="MM/YY" 
                                       required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-dark">CVV / CVC</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" 
                                       x-model="cardCvv" 
                                       maxlength="4"
                                       class="form-control font-mono" 
                                       placeholder="123" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Order Description / Memo -->
                    <div class="mb-4">
                        <label class="form-label text-dark">Order Description / Memo</label>
                        <input type="text" 
                               x-model="orderDescription" 
                               class="form-control font-mono text-sm" 
                               placeholder="e.g. Flight Booking Ticket Payment">
                    </div>

                    <!-- Real-Time Error Alert Box -->
                    <div x-show="errorMessage" 
                         x-transition
                         class="alert alert-danger bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger rounded-3 p-3 mb-3 small d-flex align-items-start gap-2">
                        <i class="bi bi-x-octagon-fill fs-5 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <div class="fw-bold fs-6" x-text="errorTitle || 'Charge Declined'"></div>
                            <div x-text="errorMessage"></div>
                            <div class="font-mono small mt-1 text-dark" x-show="errorDetails" x-text="errorDetails"></div>
                        </div>
                    </div>

                    <!-- Real-Time Success Alert Box -->
                    <div x-show="successData" 
                         x-transition
                         class="alert alert-success bg-success bg-opacity-10 border border-success border-opacity-25 text-success rounded-3 p-3 mb-3 small d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <div class="fw-bold fs-6">Transaction Approved!</div>
                            <div class="text-dark" x-text="'Authorization Code: ' + (successData?.auth_code || 'N/A')"></div>
                            <div class="font-mono small mt-1 text-secondary" x-text="'TxID: ' + successData?.transaction_id"></div>
                        </div>
                    </div>

                    <!-- Submit Charge Button -->
                    <button type="submit" 
                            class="btn btn-charge-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2 fs-6 shadow-sm"
                            :disabled="isProcessing || !selectedMerchantId || !chargeAmount || !cardNumber || !cardExpiry || !cardCvv">
                        <template x-if="!isProcessing">
                            <span class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-lock-fill fs-5"></i>
                                <span>PROCESS NMI CHARGE NOW</span>
                            </span>
                        </template>
                        <template x-if="isProcessing">
                            <span class="d-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span>Communicating with NMI Gateway...</span>
                            </span>
                        </template>
                    </button>

                </form>

            </div>

        </div>

        <!-- ========================================== -->
        <!-- BOTTOM: REAL-TIME TRANSACTION LEDGER       -->
        <!-- ========================================== -->
        <div class="col-12">
            <div class="software-card p-3 p-md-4 bg-white border">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-primary fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0">Recent Gateway Transactions Ledger</h6>
                    </div>
                    <button type="button" @click="refreshTransactions()" class="btn btn-outline-secondary btn-sm font-mono py-1 px-2.5">
                        <i class="bi bi-arrow-clockwise me-1" :class="isRefreshingLedger ? 'spin' : ''"></i>Refresh Ledger
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 font-mono small border-top">
                        <thead class="table-light text-secondary" style="font-size: 0.75rem;">
                            <tr>
                                <th>TIME</th>
                                <th>STATUS</th>
                                <th>TRANSACTION ID</th>
                                <th>MERCHANT</th>
                                <th>CUSTOMER / BOOKING</th>
                                <th>CARD</th>
                                <th class="text-end">AMOUNT</th>
                                <th class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="tx in transactionsList" :key="tx.id">
                                <tr>
                                    <td class="text-secondary" x-text="tx.processed_at"></td>
                                    <td>
                                        <span class="badge" 
                                              :class="{
                                                  'bg-success bg-opacity-15 text-success border border-success border-opacity-25': tx.status === 'approved',
                                                  'bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25': tx.status === 'declined',
                                                  'bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25': tx.status === 'error'
                                              }"
                                              x-text="tx.status.toUpperCase()">
                                        </span>
                                    </td>
                                    <td class="text-dark fw-bold" x-text="tx.transaction_id || 'N/A'"></td>
                                    <td class="text-dark" x-text="tx.merchant_name"></td>
                                    <td>
                                        <div class="text-dark fw-semibold" x-text="tx.customer_name"></div>
                                        <div class="text-muted" style="font-size: 0.7rem;" x-show="tx.booking_id" x-text="'Booking: #' + tx.booking_id"></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" x-text="(tx.card_brand || 'Card') + ' ••' + (tx.card_last4 || '--')"></span>
                                    </td>
                                    <td class="text-end fw-bold" :class="tx.status === 'approved' ? 'text-success' : 'text-secondary'" x-text="'$' + tx.amount"></td>
                                    <td class="text-center">
                                        <button type="button" @click="viewReceipt(tx)" class="btn btn-outline-primary btn-sm py-0.5 px-2 font-mono" style="font-size: 0.7rem;">
                                            <i class="bi bi-receipt me-1"></i>Receipt
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="transactionsList.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No transactions logged yet in this session.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- REAL-TIME SUCCESS RECEIPT MODAL            -->
    <!-- ========================================== -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content software-card border bg-white shadow-lg text-dark">
                <div class="modal-header border-bottom pb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-circle p-2">
                            <i class="bi bi-check-lg fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Payment Transaction Receipt</h5>
                            <span class="text-secondary small font-mono">NMI Gateway Direct Sale</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="printableReceipt">
                    <div class="text-center mb-4 pb-3 border-bottom">
                        <div class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 font-mono px-3 py-1.5 mb-2 fs-6">
                            TRANSACTION APPROVED
                        </div>
                        <h2 class="fw-bold font-mono text-success mb-0" x-text="'$' + activeReceipt?.amount"></h2>
                        <div class="text-secondary font-mono small" x-text="activeReceipt?.merchant_name"></div>
                    </div>

                    <div class="row g-2 font-mono small text-dark mb-4">
                        <div class="col-6 text-secondary">Transaction ID:</div>
                        <div class="col-6 text-end text-dark fw-bold" x-text="activeReceipt?.transaction_id || 'N/A'"></div>

                        <div class="col-6 text-secondary">Authorization Code:</div>
                        <div class="col-6 text-end text-dark fw-bold" x-text="activeReceipt?.auth_code || 'N/A'"></div>

                        <div class="col-6 text-secondary">Card Charged:</div>
                        <div class="col-6 text-end text-dark" x-text="(activeReceipt?.card_brand || 'Card') + ' •••• ' + (activeReceipt?.card_last4 || '----')"></div>

                        <div class="col-6 text-secondary">Booking Reference:</div>
                        <div class="col-6 text-end text-dark" x-text="activeReceipt?.booking_id ? ('#' + activeReceipt.booking_id) : 'Direct Entry'"></div>

                        <div class="col-6 text-secondary">Timestamp:</div>
                        <div class="col-6 text-end text-dark" x-text="activeReceipt?.processed_at || 'Just Now'"></div>

                        <div class="col-6 text-secondary" x-show="activeReceipt?.avs_description">AVS Verification:</div>
                        <div class="col-6 text-end text-primary" x-show="activeReceipt?.avs_description" x-text="activeReceipt?.avs_description"></div>

                        <div class="col-6 text-secondary" x-show="activeReceipt?.cvv_description">CVV Verification:</div>
                        <div class="col-6 text-end text-primary" x-show="activeReceipt?.cvv_description" x-text="activeReceipt?.cvv_description"></div>
                    </div>

                    <div class="p-3 rounded bg-light border small font-mono text-secondary text-center">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        Cardholder payment processed and secured via NMI Payment Gateway.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" @click="printReceipt()">
                        <i class="bi bi-printer-fill me-1"></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function merchantChargeApp() {
    return {
        // App State
        currentTime: '',
        merchants: @json($merchants),
        selectedMerchantId: {{ $merchants->first()->id ?? 'null' }},
        selectedMerchant: @json($merchants->first() ?? null),

        // Booking State
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        selectedBooking: null,
        paymentInfoNote: '',

        // Customer & Billing Info
        customerFirstName: '',
        customerLastName: '',
        customerPhone: '',
        customerEmail: '',
        billingAddress1: '',
        billingCity: '',
        billingState: '',
        billingZip: '',

        // Payment State
        chargeAmount: '',
        cardNumber: '',
        cardExpiry: '',
        cardCvv: '',
        orderDescription: '',
        formattedCardNumber: '',
        formattedExpiry: '',
        detectedCardBrand: '',

        // UI & Diagnostics
        isProcessing: false,
        isRefreshingLedger: false,
        errorMessage: '',
        errorTitle: '',
        errorDetails: '',
        successData: null,
        activeReceipt: null,
        receiptModalInstance: null,

        // Ledger
        transactionsList: @json($transactionsData ?? []),

        initApp() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            const modalEl = document.getElementById('receiptModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                this.receiptModalInstance = new bootstrap.Modal(modalEl);
            }
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        selectMerchant(merchant) {
            this.selectedMerchant = merchant;
            this.selectedMerchantId = merchant.id;
        },

        async performBookingSearch() {
            const q = this.searchQuery.trim();
            if (q.length === 0) {
                this.searchResults = [];
                return;
            }

            try {
                const response = await fetch(`{{ route('merchant.charge.search') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    this.searchResults = result.data;
                }
            } catch (err) {
                console.error('Search error:', err);
            }
        },

        selectBooking(item) {
            this.selectedBooking = item;
            this.searchResults = [];
            this.searchQuery = '';

            // Autofill customer details
            if (item.customer_name) {
                const parts = item.customer_name.split(' ');
                this.customerFirstName = parts[0] || '';
                this.customerLastName = parts.slice(1).join(' ') || '';
            }
            this.customerPhone = item.phone || '';
            this.customerEmail = item.email || '';
            this.billingAddress1 = item.billing_address || '';
            this.chargeAmount = Number(item.total_amount).toFixed(2);
            this.orderDescription = `Flight Booking #${item.booking_id} (${item.airline_pnr || 'N/A'})`;

            // If merchant id is associated, select it
            if (item.merchant_id) {
                const found = this.merchants.find(m => m.id === item.merchant_id);
                if (found) {
                    this.selectMerchant(found);
                }
            }
        },

        clearSelectedBooking() {
            this.selectedBooking = null;
            this.searchQuery = '';
            this.paymentInfoNote = '';
        },

        formatCardNumberInput(event) {
            let val = event.target.value.replace(/\D/g, '');
            this.detectCardBrand(val);

            // Group into 4 digits
            let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
            this.cardNumber = formatted;
            this.formattedCardNumber = formatted;
        },

        detectCardBrand(num) {
            if (/^4/.test(num)) {
                this.detectedCardBrand = 'visa';
            } else if (/^(5[1-5]|2[2-7])/.test(num)) {
                this.detectedCardBrand = 'mastercard';
            } else if (/^3[47]/.test(num)) {
                this.detectedCardBrand = 'amex';
            } else if (/^(6011|65|64[4-9])/.test(num)) {
                this.detectedCardBrand = 'discover';
            } else {
                this.detectedCardBrand = '';
            }
        },

        formatExpiryInput(event) {
            let val = event.target.value.replace(/\D/g, '');
            if (val.length >= 2) {
                this.cardExpiry = val.substring(0, 2) + '/' + val.substring(2, 4);
            } else {
                this.cardExpiry = val;
            }
            this.formattedExpiry = this.cardExpiry;
        },

        async processDirectCharge() {
            this.errorMessage = '';
            this.errorTitle = '';
            this.errorDetails = '';
            this.successData = null;

            if (!this.selectedMerchantId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Merchant Required',
                        text: 'Please select a target merchant gateway from Step 1.',
                        background: '#ffffff',
                        color: '#0f172a'
                    });
                }
                return;
            }

            this.isProcessing = true;

            const payload = {
                merchant_id: this.selectedMerchantId,
                booking_id: this.selectedBooking ? this.selectedBooking.id : null,
                amount: this.chargeAmount,
                ccnumber: this.cardNumber.replace(/\s/g, ''),
                ccexp: this.cardExpiry,
                cvv: this.cardCvv,
                customer_first_name: this.customerFirstName,
                customer_last_name: this.customerLastName,
                phone: this.customerPhone,
                email: this.customerEmail,
                address1: this.billingAddress1,
                city: this.billingCity,
                state: this.billingState,
                zip: this.billingZip,
                order_description: this.orderDescription,
                payment_info_note: this.paymentInfoNote,
            };

            try {
                const response = await fetch(`{{ route('merchant.charge.process') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.successData = data.data;
                    this.activeReceipt = data.data;

                    // Add to ledger
                    this.transactionsList.unshift({
                        id: Date.now(),
                        transaction_id: data.data.transaction_id,
                        order_id: data.data.order_id,
                        merchant_name: data.data.merchant_name,
                        customer_name: `${this.customerFirstName} ${this.customerLastName}`.trim() || 'N/A',
                        booking_id: data.data.booking_id,
                        card_last4: data.data.card_last4,
                        card_brand: data.data.card_brand,
                        amount: data.data.amount,
                        status: 'approved',
                        processed_at: data.data.processed_at
                    });

                    // Update merchant wallet balance and total processed volume live
                    if (data.data.merchant_id) {
                        const mIndex = this.merchants.findIndex(m => m.id === data.data.merchant_id);
                        if (mIndex !== -1) {
                            this.merchants[mIndex].wallet_balance_formatted = data.data.merchant_wallet_balance;
                            this.merchants[mIndex].wallet_balance_num = data.data.merchant_wallet_balance_num;
                            this.merchants[mIndex].total_charged_formatted = data.data.merchant_total_charged;
                            if (this.selectedMerchant && this.selectedMerchant.id === data.data.merchant_id) {
                                this.selectedMerchant = { ...this.merchants[mIndex] };
                            }
                        }
                    }

                    // Update booking payment info live if displayed
                    if (this.selectedBooking && data.data.payment_info) {
                        this.selectedBooking.payment_info = data.data.payment_info;
                    }

                    // Show Receipt Modal
                    if (this.receiptModalInstance) {
                        this.receiptModalInstance.show();
                    }

                    // Reset sensitive card fields
                    this.cardNumber = '';
                    this.formattedCardNumber = '';
                    this.cardExpiry = '';
                    this.formattedExpiry = '';
                    this.cardCvv = '';
                    this.paymentInfoNote = '';
                } else {
                    this.errorTitle = data.status ? `Charge ${data.status.toUpperCase()}` : 'Charge Failed';
                    this.errorMessage = data.message || 'Transaction was declined by payment gateway.';
                    if (data.data) {
                        this.errorDetails = `Response Code: ${data.data.response_code || 'N/A'} | AVS: ${data.data.avs_description || 'N/A'} | CVV: ${data.data.cvv_description || 'N/A'}`;
                    }

                    // Prepend declined to ledger
                    if (data.data && data.data.transaction_id) {
                        this.transactionsList.unshift({
                            id: Date.now(),
                            transaction_id: data.data.transaction_id,
                            order_id: 'N/A',
                            merchant_name: data.data.merchant_name,
                            customer_name: `${this.customerFirstName} ${this.customerLastName}`.trim() || 'N/A',
                            booking_id: this.selectedBooking ? this.selectedBooking.booking_id : null,
                            card_last4: '----',
                            card_brand: 'Card',
                            amount: Number(this.chargeAmount).toFixed(2),
                            status: 'declined',
                            processed_at: 'Just Now'
                        });
                    }
                }
            } catch (err) {
                this.errorTitle = 'Network / Gateway Error';
                this.errorMessage = 'Could not establish connection with payment gateway server.';
                console.error('Charge error:', err);
            } finally {
                this.isProcessing = false;
            }
        },

        async refreshTransactions() {
            this.isRefreshingLedger = true;
            try {
                const response = await fetch(`{{ route('merchant.charge.transactions') }}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    this.transactionsList = result.data;
                }
            } catch (err) {
                console.error('Ledger refresh error:', err);
            } finally {
                this.isRefreshingLedger = false;
            }
        },

        viewReceipt(tx) {
            this.activeReceipt = {
                amount: tx.amount,
                merchant_name: tx.merchant_name,
                transaction_id: tx.transaction_id,
                auth_code: 'LOGGED',
                card_brand: tx.card_brand,
                card_last4: tx.card_last4,
                booking_id: tx.booking_id,
                processed_at: tx.processed_at,
            };
            if (this.receiptModalInstance) {
                this.receiptModalInstance.show();
            }
        },

        printReceipt() {
            const printContent = document.getElementById('printableReceipt').innerHTML;
            const win = window.open('', '', 'height=600,width=800');
            win.document.write('<html><head><title>Payment Receipt</title>');
            win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
            win.document.write('</head><body class="p-4" style="background:#fff;color:#000;">');
            win.document.write(printContent);
            win.document.write('</body></html>');
            win.document.close();
            win.focus();
            setTimeout(() => { win.print(); win.close(); }, 500);
        }
    }
}
</script>
@endpush
@endsection
