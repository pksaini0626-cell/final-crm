@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header & Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet-fill text-danger"></i> Refund &amp; Void Request Report Sheet
            </h1>
            <p class="text-secondary small mb-0">Official MIS and Financial reporting sheet for customer refunds, voids, and partial voids with live MCO and card data.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reports.refunds.export', request()->query()) }}" class="btn btn-success fw-bold shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-file-earmark-arrow-down-fill"></i> Export CSV (All 35 Columns)
            </a>
            <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline-danger fw-bold shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-arrow-repeat"></i> Approval Desk
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-2">
            <div class="card bg-white border-light-subtle shadow-sm p-3 h-100">
                <div class="text-secondary small fw-bold text-uppercase">Total Cases</div>
                <div class="fs-4 fw-bold text-primary">{{ number_format($totalCases) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card bg-white border-light-subtle shadow-sm p-3 h-100">
                <div class="text-secondary small fw-bold text-uppercase">Approved</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($approvedCases) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card bg-white border-light-subtle shadow-sm p-3 h-100">
                <div class="text-secondary small fw-bold text-uppercase">Pending</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($pendingCases) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card bg-white border-light-subtle shadow-sm p-3 h-100">
                <div class="text-secondary small fw-bold text-uppercase">Void / Partial</div>
                <div class="fs-4 fw-bold text-dark">{{ number_format($voidCases) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card bg-white border-light-subtle shadow-sm p-3 h-100">
                <div class="text-secondary small fw-bold text-uppercase">Refunds</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($refundOnlyCases) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card bg-white border-light-subtle shadow-sm p-3 h-100">
                <div class="text-secondary small fw-bold text-uppercase">Total Refunded</div>
                @forelse($currencyTotals as $curr)
                    <div class="fs-5 fw-bold text-danger font-monospace">
                        {{ $curr->currency }} {{ number_format($curr->total_refunded, 2) }}
                    </div>
                @empty
                    <div class="fs-5 fw-bold text-muted font-monospace">$0.00</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.reports.refunds') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PNR, Booking ID, Customer..." class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <select name="request_type" class="form-select form-select-sm">
                        <option value="">All Types (Void / Refund)</option>
                        <option value="refund" {{ request('request_type') === 'refund' ? 'selected' : '' }}>Refund</option>
                        <option value="void" {{ request('request_type') === 'void' ? 'selected' : '' }}>Void</option>
                        <option value="partial_void" {{ request('request_type') === 'partial_void' ? 'selected' : '' }}>Partial Void</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All Request Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="merchant_id" class="form-select form-select-sm">
                        <option value="">All Merchants</option>
                        @foreach($merchants as $m)
                            <option value="{{ $m->id }}" {{ request('merchant_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->merchant_code ?: $m->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" placeholder="From Date">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" placeholder="To Date">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <a href="{{ route('admin.reports.refunds') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table (Displaying 35 Columns) -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-table text-danger"></i> Refund / Void Report Table (Showing {{ $refundRequests->count() }} of {{ $refundRequests->total() }} records)
            </h2>
            <span class="badge bg-light text-secondary border font-monospace">35 Fields Synchronized</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 700px;">
                <table class="table table-bordered table-hover align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                    <thead class="table-dark text-uppercase sticky-top" style="z-index: 1;">
                        <tr>
                            <th>#</th>
                            <th>Timestamp</th>
                            <th>Date of Booking</th>
                            <th>Agent Name</th>
                            <th>Travel Date</th>
                            <th>PNR</th>
                            <th>Merchant Name</th>
                            <th>Currency</th>
                            <th>Reason for Refund</th>
                            <th>Request Raised By</th>
                            <th>Approved By</th>
                            <th>Booking Type</th>
                            <th>Company Card Used</th>
                            <th>Amount / na</th>
                            <th>Card Last 4</th>
                            <th>Card Holder's Name</th>
                            <th>Email Address</th>
                            <th>Billing Phone</th>
                            <th>Refund Amount</th>
                            <th>Request Type</th>
                            <th>Remarks</th>
                            <th>Verticals</th>
                            <th>MIS Remarks</th>
                            <th>Refund Age</th>
                            <th>Refund Date</th>
                            <th>Status (Payment)</th>
                            <th>TL/Admin Remarks</th>
                            <th>Month</th>
                            <th>Actioned By</th>
                            <th>Deduction from Agent</th>
                            <th>Email Sent By</th>
                            <th>Actioned in Month</th>
                            <th>Receipt to CS</th>
                            <th>Updated on MCO</th>
                            <th>Merchant as per MIS</th>
                            <th>Dub/Unique</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($refundRequests as $idx => $item)
                            @php
                                $b = $item->booking;
                                $mObj = $b ? $b->merchantProfile : null;
                            @endphp
                            <tr>
                                <td class="text-center font-monospace">{{ $refundRequests->firstItem() + $idx }}</td>
                                <td>{{ $item->created_at ? $item->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>{{ ($b && $b->booking_date) ? \Carbon\Carbon::parse($b->booking_date)->format('Y-m-d') : 'N/A' }}</td>
                                <td class="fw-semibold">{{ ($b && $b->agent) ? ($b->agent->alias_name ?: $b->agent->name) : 'N/A' }}</td>
                                <td>{{ ($b && $b->travel_date) ? \Carbon\Carbon::parse($b->travel_date)->format('Y-m-d') : 'N/A' }}</td>
                                <td class="font-monospace fw-bold text-primary">{{ $b ? ($b->airline_pnr ?: ($b->gk_pnr ?: $b->booking_id)) : 'N/A' }}</td>
                                <td>
                                    {{ $mObj ? $mObj->name . ' (' . ($mObj->merchant_code ?: $mObj->code) . ')' : ($b ? $b->merchant : 'N/A') }}
                                </td>
                                <td class="font-monospace fw-bold">{{ $b ? ($b->currency ?: 'USD') : ($item->currency ?: 'USD') }}</td>
                                <td class="text-wrap" style="max-width: 200px;">{{ $item->reason_for_refund }}</td>
                                <td>{{ $item->agent ? ($item->agent->alias_name ?: $item->agent->name) : 'N/A' }}</td>
                                <td class="fw-semibold text-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $item->approver ? ($item->approver->alias_name ?: $item->approver->name) : ($item->status === 'approved' ? 'Approved' : 'Pending') }}
                                </td>
                                <td>{{ $b ? ($b->booking_portal ?: ($b->service_provided ?: 'Flight')) : 'Flight' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ ($b && $b->company_card_used) ? 'bg-primary' : 'bg-light text-dark border' }}">
                                        {{ ($b && $b->company_card_used) ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="font-monospace">
                                    {{ ($b && $b->company_card_used && $b->company_card_amount > 0) ? number_format((float)$b->company_card_amount, 2) : 'na' }}
                                </td>
                                <td class="font-monospace">**** {{ $b ? ($b->card_last_4 ?: 'N/A') : 'N/A' }}</td>
                                <td class="fw-semibold">{{ $b ? ($b->card_holder_name ?: 'N/A') : 'N/A' }}</td>
                                <td>{{ $b ? $b->email_address : 'N/A' }}</td>
                                <td>{{ $b ? ($b->billing_phone ?: ($b->calling_number ?: 'N/A')) : 'N/A' }}</td>
                                <td class="font-monospace fw-bold text-danger fs-6">
                                    {{ number_format((float)$item->refund_amount, 2) }}
                                </td>
                                <td>
                                    @if($item->request_type === 'void')
                                        <span class="badge bg-dark font-monospace">VOID</span>
                                    @elseif($item->request_type === 'partial_void')
                                        <span class="badge bg-secondary font-monospace">PARTIAL VOID</span>
                                    @else
                                        <span class="badge bg-danger font-monospace">REFUND</span>
                                    @endif
                                </td>
                                <td class="text-wrap" style="max-width: 250px;">{{ $item->remarks }}</td>
                                <td>{{ $b ? ucfirst($b->vertical ?: 'Flight') : 'Flight' }}</td>
                                <td class="text-wrap" style="max-width: 200px;">{{ $item->mis_remarks ?: '-' }}</td>
                                <td class="font-monospace">{{ $item->refund_age_days }} days</td>
                                <td>{{ $item->refund_date ? \Carbon\Carbon::parse($item->refund_date)->format('Y-m-d') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace text-uppercase">
                                        {{ $b ? $b->payment_status : $item->status }}
                                    </span>
                                </td>
                                <td>{{ $item->admin_remarks ?: '-' }}</td>
                                <td>{{ $item->refund_month }}</td>
                                <td>{{ $item->approver ? ($item->approver->alias_name ?: $item->approver->name) : '-' }}</td>
                                <td>{{ $item->deduction_from_agent ?: 'nil' }}</td>
                                <td>{{ $item->email_sent_to_agent_by ?: 'nil' }}</td>
                                <td>{{ $item->actioned_month }}</td>
                                <td>{{ $item->receipt_sent_to_cs ?: 'nil' }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        Yes ({{ $b ? $b->payment_status : $item->request_type }})
                                    </span>
                                </td>
                                <td class="font-monospace">
                                    {{ $mObj ? ($mObj->merchant_code ?: ($mObj->code ?: $mObj->name)) : ($b ? $b->merchant : 'N/A') }}
                                </td>
                                <td>{{ $item->is_duplicate ?: 'nil' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="36" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No records found matching the filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($refundRequests->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                {{ $refundRequests->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
