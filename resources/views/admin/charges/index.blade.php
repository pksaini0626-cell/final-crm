@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4" x-data="adminChargesData()">
    <!-- Page Title & Header Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-credit-card-2-front text-primary me-2"></i>Merchant Charges & Payment Links
            </h2>
            <p class="text-secondary mb-0">Charge customer credit cards directly or generate shareable payment links tied to active merchants.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-success fw-semibold shadow-sm rounded-3 px-3 py-2" data-bs-toggle="modal" data-bs-target="#directChargeModal">
                <i class="bi bi-lightning-charge-fill me-1"></i> Direct Charge (Card)
            </button>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm rounded-3 px-3 py-2" data-bs-toggle="modal" data-bs-target="#createLinkModal">
                <i class="bi bi-link-45deg me-1"></i> Generate Payment Link
            </button>
        </div>
    </div>

    <!-- Statistics Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="p-3 bg-success-subtle text-success rounded-3 fs-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Processed</div>
                        <div class="fs-4 fw-bold text-dark">${{ number_format($totalCharged, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary-subtle text-primary rounded-3 fs-3">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Active Links</div>
                        <div class="fs-4 fw-bold text-dark">{{ $activeLinksCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="p-3 bg-info-subtle text-info rounded-3 fs-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Paid Links</div>
                        <div class="fs-4 fw-bold text-dark">{{ $paidLinksCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="p-3 bg-warning-subtle text-warning rounded-3 fs-3">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Transactions</div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalTransactionsCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs for Links & Transactions -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-bottom p-3">
            <ul class="nav nav-tabs card-header-tabs fw-semibold" id="chargeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="links-tab" data-bs-toggle="tab" data-bs-target="#links-pane" type="button" role="tab" aria-controls="links-pane" aria-selected="true">
                        <i class="bi bi-link-45deg me-1"></i> Payment Links ({{ $paymentLinks->total() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions-pane" type="button" role="tab" aria-controls="transactions-pane" aria-selected="false">
                        <i class="bi bi-receipt me-1"></i> NMI Transaction Logs ({{ $transactions->total() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="chargeTabsContent">
                <!-- Payment Links Tab -->
                <div class="tab-pane fade show active" id="links-pane" role="tabpanel" aria-labelledby="links-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase fw-semibold">
                                <tr>
                                    <th class="ps-3 py-3">Link / Customer</th>
                                    <th>Merchant</th>
                                    <th>Booking Ref</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Expires At</th>
                                    <th>Created By</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentLinks as $link)
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark">{{ $link->customer_full_name ?: 'N/A' }}</div>
                                        <div class="small text-muted">{{ $link->email }} {{ $link->phone ? '• ' . $link->phone : '' }}</div>
                                        <div class="mt-1 d-flex align-items-center gap-1">
                                            <span class="badge bg-light text-dark font-monospace border small px-2 py-1">
                                                {{ Str::limit($link->token, 12, '...') }}
                                            </span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1.5" title="Copy Link" @click="copyToClipboard('{{ $link->public_url }}')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                            <a href="{{ $link->public_url }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1.5" title="Open Link in New Tab">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-dark fw-semibold px-2.5 py-1.5">
                                            <i class="bi bi-building me-1"></i>{{ $link->merchant->name ?? 'Default Merchant' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($link->booking)
                                            <a href="{{ route('bookings.index', ['search' => $link->booking->pnr]) }}" class="badge bg-info-subtle text-info-emphasis text-decoration-none fw-semibold px-2.5 py-1.5">
                                                PNR: {{ $link->booking->pnr }}
                                            </a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark fs-6">
                                        ${{ number_format($link->amount, 2) }} <small class="text-muted">{{ $link->currency }}</small>
                                    </td>
                                    <td>
                                        @if($link->status === 'paid')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i> Paid
                                            </span>
                                        @elseif($link->isExpired())
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-clock-history me-1"></i> Expired
                                            </span>
                                        @elseif($link->status === 'cancelled')
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-slash-circle me-1"></i> Cancelled
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-hourglass-split me-1"></i> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-secondary">
                                        {{ $link->expires_at ? $link->expires_at->format('M d, Y h:i A') : 'No Expiry' }}
                                    </td>
                                    <td class="small">
                                        {{ $link->creator->alias_name ?? 'Admin' }}
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($link->status === 'pending' && !$link->isExpired())
                                            <form action="{{ route('admin.charges.cancel-link', $link->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this payment link?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-2">
                                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i> No payment links generated yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top">
                        {{ $paymentLinks->appends(request()->except('links_page'))->links() }}
                    </div>
                </div>

                <!-- NMI Transactions Pane -->
                <div class="tab-pane fade" id="transactions-pane" role="tabpanel" aria-labelledby="transactions-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase fw-semibold">
                                <tr>
                                    <th class="ps-3 py-3">Tx ID / Date</th>
                                    <th>Merchant</th>
                                    <th>Customer</th>
                                    <th>Card Info</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Booking / Link</th>
                                    <th class="text-end pe-3">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $tx)
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold font-monospace text-primary">{{ $tx->transaction_id ?: 'N/A' }}</div>
                                        <div class="small text-muted">{{ $tx->processed_at ? $tx->processed_at->format('M d, Y h:i A') : $tx->created_at->format('M d, Y h:i A') }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-semibold px-2.5 py-1.5">
                                            {{ $tx->merchant->name ?? 'Default Merchant' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $tx->customer_full_name ?: 'N/A' }}</div>
                                        <div class="small text-muted">{{ $tx->email }}</div>
                                    </td>
                                    <td>
                                        @if($tx->card_last4)
                                            <span class="badge bg-secondary-subtle text-dark font-monospace px-2 py-1">
                                                <i class="bi bi-credit-card me-1"></i>{{ strtoupper($tx->card_brand ?: 'CARD') }} ****{{ $tx->card_last4 }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark fs-6">
                                        ${{ number_format($tx->amount, 2) }}
                                    </td>
                                    <td>
                                        @if($tx->status === 'approved')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i> Approved
                                            </span>
                                        @elseif($tx->status === 'declined')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-x-circle-fill me-1"></i> Declined
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5 rounded-pill">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Error
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($tx->booking)
                                            <div>PNR: <strong>{{ $tx->booking->pnr }}</strong></div>
                                        @endif
                                        @if($tx->payment_link_id)
                                            <div class="text-muted">Link #{{ $tx->payment_link_id }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1 rounded-2" @click="openResponseModal({{ json_encode($tx->raw_response) }})">
                                            <i class="bi bi-code-slash me-1"></i> Response
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i> No transactions logged yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top">
                        {{ $transactions->appends(request()->except('tx_page'))->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Generate Payment Link -->
    <div class="modal fade" id="createLinkModal" tabindex="-1" aria-labelledby="createLinkLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('admin.charges.store-link') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="createLinkLabel">
                            <i class="bi bi-link-45deg me-1"></i> Generate Customer Payment Link
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Merchant & Optional Booking Select -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Select Merchant Account <span class="text-danger">*</span></label>
                                <select name="merchant_id" class="form-select" required x-model="linkForm.merchant_id">
                                    <option value="">-- Choose Merchant Account --</option>
                                    @foreach($merchants as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->currency ?: 'USD' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Link to Booking (Optional)</label>
                                <select name="booking_id" class="form-select" x-model="linkForm.booking_id" @change="onBookingSelect(linkForm.booking_id, 'link')">
                                    <option value="">-- Select Booking (Optional) --</option>
                                    @foreach($bookings as $b)
                                        <option value="{{ $b->id }}">PNR: {{ $b->pnr }} - {{ $b->passenger_name }} (${{ number_format($b->total_amount, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-muted">

                        <!-- Customer Info -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer First Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_first_name" class="form-control" placeholder="e.g. John" required x-model="linkForm.customer_first_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_last_name" class="form-control" placeholder="e.g. Doe" required x-model="linkForm.customer_last_name">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer Email</label>
                                <input type="email" name="email" class="form-control" placeholder="john@example.com" x-model="linkForm.email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="+1 555-0199" x-model="linkForm.phone">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Amount to Charge ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">$</span>
                                    <input type="number" step="0.01" name="amount" class="form-control fw-bold fs-5 text-primary" placeholder="0.00" required x-model="linkForm.amount">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Link Expiration (Hours)</label>
                                <input type="number" name="expiry_hours" class="form-control" value="48" min="1" placeholder="48">
                                <small class="text-muted">Default is 48 hours (2 days).</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description / Notes for Customer</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="e.g. Flight ticket payment for PNR #ABC123"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3 shadow-sm">
                            <i class="bi bi-magic me-1"></i> Generate Payment Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Direct Charge Credit Card -->
    <div class="modal fade" id="directChargeModal" tabindex="-1" aria-labelledby="directChargeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('admin.charges.direct') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="directChargeLabel">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Direct Credit Card Charge
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Merchant & PNR Search Input -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Select Merchant Account <span class="text-danger">*</span></label>
                                <select name="merchant_id" class="form-select" required x-model="directForm.merchant_id">
                                    <option value="">-- Choose Merchant Account --</option>
                                    @foreach($merchants as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->currency ?: 'USD' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Link to Booking / Airline PNR (Optional)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Enter Airline PNR or Booking ID" x-model="pnrSearchQuery" @keydown.enter.prevent="fetchBookingByPnr()">
                                    <button class="btn btn-outline-primary fw-semibold" type="button" @click="fetchBookingByPnr()" :disabled="fetchingBooking">
                                        <span x-show="!fetchingBooking"><i class="bi bi-search me-1"></i> Fetch</span>
                                        <span x-show="fetchingBooking" x-cloak><span class="spinner-border spinner-border-sm me-1"></span> Fetching...</span>
                                    </button>
                                </div>
                                <input type="hidden" name="booking_id" x-model="directForm.booking_id">
                                <template x-if="pnrMessage">
                                    <small class="d-block mt-1 fw-semibold" :class="pnrSuccess ? 'text-success' : 'text-danger'" x-text="pnrMessage"></template>
                                </template>
                            </div>
                        </div>

                        <hr class="my-3 text-muted">

                        <!-- Customer Info (Email field removed) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer First Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_first_name" class="form-control" placeholder="John" required x-model="directForm.customer_first_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_last_name" class="form-control" placeholder="Doe" required x-model="directForm.customer_last_name">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="+1 555-0199" x-model="directForm.phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Amount to Charge ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">$</span>
                                    <input type="number" step="0.01" name="amount" class="form-control fw-bold fs-5 text-success" placeholder="0.00" required x-model="directForm.amount">
                                </div>
                            </div>
                        </div>

                        <!-- Card Details -->
                        <div class="card border border-success-subtle bg-light-subtle p-3 mb-3 rounded-3">
                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-credit-card-fill me-1"></i> Card Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Card Number <span class="text-danger">*</span></label>
                                <input type="text" name="ccnumber" class="form-control font-monospace" placeholder="4111 2222 3333 4444" required>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Expiration Date (MMYY) <span class="text-danger">*</span></label>
                                    <input type="text" name="ccexp" class="form-control font-monospace" placeholder="1228" maxlength="4" required>
                                    <small class="text-muted">Format: MMYY (e.g. 1228 for Dec 2028)</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">CVV / Security Code <span class="text-danger">*</span></label>
                                    <input type="text" name="cvv" class="form-control font-monospace" placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Billing Address</label>
                            <input type="text" name="address1" class="form-control mb-2" placeholder="Street Address" x-model="directForm.address1">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="city" class="form-control" placeholder="City" x-model="directForm.city">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="state" class="form-control" placeholder="State/Prov" x-model="directForm.state">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="zip" class="form-control" placeholder="Zip Code" x-model="directForm.zip">
                                </div>
                            </div>
                        </div>

                        <!-- Order Description Field -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark">Order Description / Notes (Optional)</label>
                            <textarea name="order_description" class="form-control" rows="2" placeholder="e.g. Flight ticket change fee or seat selection for PNR #ABC123"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success fw-bold px-4 rounded-3 shadow-sm">
                            <i class="bi bi-shield-check me-1"></i> Charge Card Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: View Raw Response -->
    <div class="modal fade" id="rawResponseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-monospace fs-6">
                        <i class="bi bi-code-slash me-1"></i> Gateway Raw Response Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <pre class="bg-dark text-success p-3 m-0 rounded-bottom font-monospace small" style="max-height: 400px; overflow-y: auto;" x-text="rawResponseJson"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function adminChargesData() {
        return {
            pnrSearchQuery: '',
            fetchingBooking: false,
            pnrMessage: '',
            pnrSuccess: false,
            linkForm: {
                merchant_id: '',
                booking_id: '',
                customer_first_name: '',
                customer_last_name: '',
                email: '',
                phone: '',
                amount: ''
            },
            directForm: {
                merchant_id: '',
                booking_id: '',
                customer_first_name: '',
                customer_last_name: '',
                phone: '',
                amount: '',
                address1: '',
                city: '',
                state: '',
                zip: ''
            },
            rawResponseJson: '',

            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Payment link copied to clipboard!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            },

            fetchBookingByPnr() {
                if (!this.pnrSearchQuery.trim()) {
                    this.pnrMessage = 'Please enter an Airline PNR or Booking ID.';
                    this.pnrSuccess = false;
                    return;
                }
                this.fetchingBooking = true;
                this.pnrMessage = '';

                fetch('/admin/charges/find-booking?pnr=' + encodeURIComponent(this.pnrSearchQuery.trim()))
                    .then(res => res.json())
                    .then(data => {
                        this.fetchingBooking = false;
                        if (data.success) {
                            this.directForm.booking_id = data.id;
                            this.directForm.customer_first_name = data.customer_first_name || '';
                            this.directForm.customer_last_name = data.customer_last_name || '';
                            this.directForm.phone = data.phone || '';
                            this.directForm.amount = data.amount || '';
                            if (data.merchant_id) {
                                this.directForm.merchant_id = data.merchant_id;
                            }
                            this.directForm.address1 = data.billing_address || '';
                            this.directForm.city = data.billing_city || '';
                            this.directForm.state = data.billing_state || '';
                            this.directForm.zip = data.billing_zip || '';

                            this.pnrSuccess = true;
                            this.pnrMessage = `✓ Booking Found! PNR: ${data.pnr} (${data.customer_first_name} ${data.customer_last_name})`;
                        } else {
                            this.pnrSuccess = false;
                            this.pnrMessage = data.message || 'No booking found.';
                        }
                    })
                    .catch(err => {
                        this.fetchingBooking = false;
                        this.pnrSuccess = false;
                        this.pnrMessage = 'Failed to fetch booking details.';
                        console.error(err);
                    });
            },

            onBookingSelect(bookingId, formType) {
                if (!bookingId) return;

                fetch('/admin/charges/booking-details/' + bookingId)
                    .then(res => res.json())
                    .then(data => {
                        let target = formType === 'link' ? this.linkForm : this.directForm;
                        target.customer_first_name = data.customer_first_name || '';
                        target.customer_last_name = data.customer_last_name || '';
                        if (formType === 'link') {
                            target.email = data.email || '';
                        }
                        target.phone = data.phone || '';
                        target.amount = data.amount || '';
                        if (data.merchant_id) {
                            target.merchant_id = data.merchant_id;
                        }
                        if (formType === 'direct') {
                            target.address1 = data.billing_address || '';
                            target.city = data.billing_city || '';
                            target.state = data.billing_state || '';
                            target.zip = data.billing_zip || '';
                        }
                    })
                    .catch(err => console.error(err));
            },

            openResponseModal(rawObj) {
                this.rawResponseJson = JSON.stringify(rawObj, null, 2);
                let modal = new bootstrap.Modal(document.getElementById('rawResponseModal'));
                modal.show();
            }
        }
    }
</script>
@endsection
