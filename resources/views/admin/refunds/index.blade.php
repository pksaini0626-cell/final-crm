@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat text-danger"></i> Refund &amp; Void Requests Desk
            </h1>
            <p class="text-secondary small mb-0">Review, approve, or reject customer refund and void requests raised by agents. Synced directly with MCO reports.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reports.refunds') }}" class="btn btn-outline-danger btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> View Report Sheet
            </a>
            <a href="{{ route('admin.reports.refunds.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Status Tabs & Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.refunds.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}" class="card text-decoration-none border-{{ $status === 'pending' ? 'warning' : 'light-subtle' }} shadow-sm h-100 hover-shadow transition">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase">Pending Approval</div>
                        <div class="fs-3 fw-bold text-warning">{{ $pendingCount }}</div>
                    </div>
                    <div class="p-3 bg-warning-subtle text-warning rounded-circle">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.refunds.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}" class="card text-decoration-none border-{{ $status === 'approved' ? 'success' : 'light-subtle' }} shadow-sm h-100 hover-shadow transition">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase">Approved &amp; Refunded</div>
                        <div class="fs-3 fw-bold text-success">{{ $approvedCount }}</div>
                    </div>
                    <div class="p-3 bg-success-subtle text-success rounded-circle">
                        <i class="bi bi-check2-all fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.refunds.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}" class="card text-decoration-none border-{{ $status === 'rejected' ? 'danger' : 'light-subtle' }} shadow-sm h-100 hover-shadow transition">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase">Rejected Cases</div>
                        <div class="fs-3 fw-bold text-danger">{{ $rejectedCount }}</div>
                    </div>
                    <div class="p-3 bg-danger-subtle text-danger rounded-circle">
                        <i class="bi bi-x-circle fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.refunds.index', array_merge(request()->except('page'), ['status' => 'all'])) }}" class="card text-decoration-none border-{{ $status === 'all' ? 'primary' : 'light-subtle' }} shadow-sm h-100 hover-shadow transition">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase">Total Cases</div>
                        <div class="fs-3 fw-bold text-primary">{{ $totalCount }}</div>
                    </div>
                    <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                        <i class="bi bi-collection-fill fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.refunds.index') }}" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PNR, Booking ID, Customer..." class="form-control">
                    </div>
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
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" placeholder="From Date">
                </div>

                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" placeholder="To Date">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.refunds.index', ['status' => $status]) }}" class="btn btn-outline-secondary btn-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-secondary">
                        <tr>
                            <th class="ps-3">Req ID / Booking</th>
                            <th>Customer &amp; PNR</th>
                            <th>Merchant</th>
                            <th>Request Type</th>
                            <th>Refund Amount</th>
                            <th>Refund Date / Age</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>MIS / TL Notes</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($refundRequests as $req)
                            @php
                                $b = $req->booking;
                                $mObj = $b ? $b->merchantProfile : null;
                            @endphp
                            <tr>
                                <!-- Req ID / Booking -->
                                <td class="ps-3">
                                    <div class="fw-bold font-monospace text-primary">#{{ $b ? $b->booking_id : 'N/A' }}</div>
                                    <div class="small text-muted font-monospace">Req #{{ $req->id }}</div>
                                    <div class="small text-muted">{{ $req->created_at->format('M d, H:i') }}</div>
                                </td>

                                <!-- Customer & PNR -->
                                <td>
                                    <div class="fw-semibold text-dark">{{ $b ? ($b->card_holder_name ?: 'N/A') : 'N/A' }}</div>
                                    <div class="small text-muted">PNR: <span class="fw-bold font-monospace text-dark">{{ $b ? ($b->airline_pnr ?: ($b->gk_pnr ?: 'N/A')) : 'N/A' }}</span></div>
                                    <div class="small text-muted">{{ $b ? $b->email_address : '' }}</div>
                                </td>

                                <!-- Merchant -->
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        {{ $mObj ? $mObj->name : ($b ? $b->merchant : 'N/A') }}
                                    </div>
                                    @if($mObj && ($mObj->merchant_code || $mObj->code))
                                        <span class="badge bg-light text-secondary border font-monospace small">
                                            {{ $mObj->merchant_code ?: $mObj->code }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Request Type -->
                                <td>
                                    @if($req->request_type === 'void')
                                        <span class="badge bg-dark font-monospace text-uppercase">VOID</span>
                                    @elseif($req->request_type === 'partial_void')
                                        <span class="badge bg-secondary font-monospace text-uppercase">PARTIAL VOID</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace text-uppercase">REFUND</span>
                                    @endif
                                    <div class="small text-muted mt-1 text-truncate" style="max-width: 150px;" title="{{ $req->reason_for_refund }}">
                                        {{ $req->reason_for_refund }}
                                    </div>
                                </td>

                                <!-- Refund Amount -->
                                <td>
                                    <div class="fw-bold font-monospace fs-6 text-danger">
                                        {{ $req->currency }} {{ number_format($req->refund_amount, 2) }}
                                    </div>
                                    <div class="small text-muted">
                                        Total MCO: {{ $req->currency }} {{ $b ? number_format($b->total_mco, 2) : '0.00' }}
                                    </div>
                                </td>

                                <!-- Refund Date / Age -->
                                <td>
                                    <div class="small text-dark fw-semibold">
                                        {{ $req->refund_date ? $req->refund_date->format('M d, Y') : 'N/A' }}
                                    </div>
                                    <span class="badge bg-light text-secondary border font-monospace">
                                        Age: {{ $req->refund_age_days }} days
                                    </span>
                                </td>

                                <!-- Requested By -->
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $req->agent ? ($req->agent->alias_name ?: $req->agent->name) : 'Agent' }}</div>
                                    @if($req->status === 'approved' && $req->approver)
                                        <div class="small text-success">Appr: {{ $req->approver->alias_name ?: $req->approver->name }}</div>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td>
                                    @if($req->status === 'approved')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace text-uppercase">Approved</span>
                                        <div class="small text-muted">Payment: {{ strtoupper(str_replace('_', ' ', $b ? $b->payment_status : '')) }}</div>
                                    @elseif($req->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace text-uppercase">Rejected</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace text-uppercase">Pending</span>
                                    @endif
                                </td>

                                <!-- MIS / TL Notes -->
                                <td style="max-width: 180px;">
                                    @if($req->mis_remarks)
                                        <div class="small text-dark fw-medium text-truncate" title="MIS: {{ $req->mis_remarks }}">
                                            <span class="badge bg-info-subtle text-info border">MIS</span> {{ $req->mis_remarks }}
                                        </div>
                                    @endif
                                    @if($req->admin_remarks)
                                        <div class="small text-secondary text-truncate" title="Admin: {{ $req->admin_remarks }}">
                                            <span class="badge bg-secondary-subtle text-secondary border">TL</span> {{ $req->admin_remarks }}
                                        </div>
                                    @endif
                                    <button type="button" class="btn btn-link btn-sm p-0 small text-decoration-none" onclick="openMisModal({{ json_encode($req) }})">
                                        <i class="bi bi-pencil-square"></i> Edit MIS
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        @if($req->status === 'pending')
                                            <button type="button" class="btn btn-success fw-bold" onclick="openApproveModal({{ json_encode($req) }})" title="Approve Refund">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                            <button type="button" class="btn btn-outline-danger fw-bold" onclick="openRejectModal({{ json_encode($req) }})" title="Reject Request">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-secondary" onclick="openViewDetails({{ json_encode($req) }})" title="View Details">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No refund or void requests found matching the current criteria.
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

<!-- Approve Refund Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title h6 fw-bold text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i> Approve Refund / Void Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-success-subtle border border-success-subtle text-success small mb-3">
                        Approving this request will automatically refund the customer value, update the booking payment status, and register the actioned status on MCO sheets.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">Confirm / Adjust Refund Amount</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold" id="apprCurrency">USD</span>
                            <input type="number" step="0.01" min="0.01" name="refund_amount" id="apprAmount" required class="form-control fw-bold text-danger">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">Refund Date</label>
                        <input type="date" name="refund_date" id="apprDate" required class="form-control form-control-sm">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">MIS Remarks (Optional)</label>
                        <textarea name="mis_remarks" id="apprMisRemarks" rows="2" placeholder="Notes for MIS records, incentive calculation, or wallet credit adjustments..." class="form-control form-control-sm"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">Admin / Approval Remarks (Optional)</label>
                        <textarea name="admin_remarks" rows="2" placeholder="Approval justification or manager notes..." class="form-control form-control-sm"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark text-uppercase">Deduction from Agent (Incentive Calculation)</label>
                        <input type="text" name="deduction_from_agent" value="nil" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                        <i class="bi bi-check-lg me-1"></i> Confirm &amp; Approve Refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title h6 fw-bold text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle-fill"></i> Reject Refund / Void Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-danger-subtle border border-danger-subtle text-danger small mb-3">
                        Rejecting will keep the booking active, restore payment status to received, and notify the agent.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" required rows="3" placeholder="Provide clear reason why refund request is being rejected..." class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold px-3">
                        <i class="bi bi-x-circle me-1"></i> Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit MIS Remarks Modal -->
<div class="modal fade" id="misModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title h6 fw-bold text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square"></i> Update MIS Data &amp; Remarks
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="misForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">MIS Remarks</label>
                        <textarea name="mis_remarks" id="misRemarksInput" rows="3" placeholder="Enter updated MIS notes or observations..." class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark text-uppercase">Deduction from Agent (Incentive Calculation)</label>
                        <input type="text" name="deduction_from_agent" id="misDeductionInput" class="form-control form-control-sm">
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-2">
                            <label class="form-label small fw-bold text-dark text-uppercase">Email Sent To Agent By</label>
                            <input type="text" name="email_sent_to_agent_by" id="misEmailSentInput" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label small fw-bold text-dark text-uppercase">Receipt Sent to CS</label>
                            <input type="text" name="receipt_sent_to_cs" id="misReceiptSentInput" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                        <i class="bi bi-save me-1"></i> Save MIS Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title h6 fw-bold text-dark text-uppercase" id="detailsTitle">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailsBody">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>
</div>

<script>
function openApproveModal(req) {
    const form = document.getElementById('approveForm');
    form.action = `/admin/refunds/${req.id}/approve`;
    document.getElementById('apprCurrency').textContent = req.currency || 'USD';
    document.getElementById('apprAmount').value = parseFloat(req.refund_amount).toFixed(2);
    document.getElementById('apprDate').value = req.refund_date ? req.refund_date.substring(0, 10) : new Date().toISOString().substring(0, 10);
    document.getElementById('apprMisRemarks').value = req.mis_remarks || '';
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function openRejectModal(req) {
    const form = document.getElementById('rejectForm');
    form.action = `/admin/refunds/${req.id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function openMisModal(req) {
    const form = document.getElementById('misForm');
    form.action = `/admin/refunds/${req.id}/update-remarks`;
    document.getElementById('misRemarksInput').value = req.mis_remarks || '';
    document.getElementById('misDeductionInput').value = req.deduction_from_agent || 'nil';
    document.getElementById('misEmailSentInput').value = req.email_sent_to_agent_by || 'nil';
    document.getElementById('misReceiptSentInput').value = req.receipt_sent_to_cs || 'nil';
    new bootstrap.Modal(document.getElementById('misModal')).show();
}

function openViewDetails(req) {
    document.getElementById('detailsTitle').textContent = `Refund Request #${req.id} - Booking #${req.booking ? req.booking.booking_id : 'N/A'}`;
    const body = document.getElementById('detailsBody');
    body.innerHTML = `
        <div class="row g-3">
            <div class="col-sm-4"><div class="text-secondary small">Request Type</div><div class="fw-bold text-uppercase">${req.request_type}</div></div>
            <div class="col-sm-4"><div class="text-secondary small">Refund Amount</div><div class="fw-bold text-danger">${req.currency} ${parseFloat(req.refund_amount).toFixed(2)}</div></div>
            <div class="col-sm-4"><div class="text-secondary small">Status</div><div class="fw-bold text-uppercase">${req.status}</div></div>
            <div class="col-12"><div class="text-secondary small">Reason for Refund</div><div class="text-dark bg-light p-2 rounded">${req.reason_for_refund || 'N/A'}</div></div>
            <div class="col-12"><div class="text-secondary small">Agent Remarks</div><div class="text-dark bg-light p-2 rounded">${req.remarks || 'N/A'}</div></div>
            <div class="col-6"><div class="text-secondary small">MIS Remarks</div><div class="text-dark bg-light p-2 rounded">${req.mis_remarks || 'None'}</div></div>
            <div class="col-6"><div class="text-secondary small">Admin Remarks</div><div class="text-dark bg-light p-2 rounded">${req.admin_remarks || 'None'}</div></div>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>
@endsection
