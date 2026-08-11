@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" x-data="changesQueueApp()">

    <!-- Header Row -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat text-primary"></i> Changes Queue Panel
            </h1>
            <p class="text-secondary small mb-0">Manage agent booking change requests, process itinerary modifications, record airline payment & FOP, attach media, and update status.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 font-monospace">
                <i class="bi bi-clock-history me-1"></i> Real-time Queue: {{ $changeRequests->total() }} Total
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('changes.index') }}" class="row g-2 align-items-center">
                <!-- Search Keyword -->
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-light-subtle text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PNR, Booking ID, request type, FOP, customer..." class="form-control bg-white border-light-subtle text-dark">
                    </div>
                </div>

                <!-- Extended Status Filter -->
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        <option value="">-- All Request Statuses --</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        <option value="denied" {{ request('status') === 'denied' ? 'selected' : '' }}>Denied</option>
                        <option value="follow_up" {{ request('status') === 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                        <option value="assigned_to_agent" {{ request('status') === 'assigned_to_agent' ? 'selected' : '' }}>Assigned to Agent</option>
                        <option value="awaiting_revert_from_airline" {{ request('status') === 'awaiting_revert_from_airline' ? 'selected' : '' }}>Awaiting Revert from Airline</option>
                        <option value="sale_cancelled" {{ request('status') === 'sale_cancelled' ? 'selected' : '' }}>Sale Cancelled</option>
                        <option value="chargeback" {{ request('status') === 'chargeback' ? 'selected' : '' }}>Chargeback</option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Voided</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="working" {{ request('status') === 'working' ? 'selected' : '' }}>Working</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div class="col-md-3">
                    <input type="date" name="date" value="{{ request('date') }}" title="Filter by Assigned Date" class="form-control form-control-sm bg-white border-light-subtle text-dark">
                </div>

                <!-- Actions -->
                <div class="col-md-2 d-flex align-items-center gap-1.5 ms-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'date']))
                        <a href="{{ route('changes.index') }}" class="btn btn-outline-secondary btn-sm px-2.5" title="Clear Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Listing Table Card -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-list-task text-primary"></i> Change Requests Listing
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">{{ $changeRequests->total() }} total</span>
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-white table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr class="small text-uppercase text-secondary border-light-subtle bg-light">
                            <th style="width: 70px;">Req #</th>
                            <th>Booking &amp; Customer Details</th>
                            <th>Requested By Agent</th>
                            <th>Request Type &amp; Details</th>
                            <th>Changes Team Notes &amp; Payment</th>
                            <th style="width: 140px;" class="text-center">Status</th>
                            <th style="width: 140px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeRequests as $req)
                            <tr>
                                <td class="font-monospace text-secondary fw-bold">#{{ $req->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-journal-text text-primary"></i>
                                        #{{ $req->booking ? $req->booking->booking_id : 'N/A' }}
                                        @if($req->booking && $req->booking->airline_pnr)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace ms-1">PNR: {{ $req->booking->airline_pnr }}</span>
                                        @endif
                                    </div>
                                    @if($req->booking && $req->booking->card_holder_name)
                                        <div class="text-secondary small fw-semibold">{{ $req->booking->card_holder_name }}</div>
                                    @endif
                                    @if($req->booking && $req->booking->email_address)
                                        <div class="text-muted font-monospace small" style="font-size: 0.75rem;">{{ $req->booking->email_address }}</div>
                                    @endif
                                    @if($req->booking && $req->booking->booking_status)
                                        <div class="mt-1">
                                            <span class="badge bg-info-subtle text-info border border-info-subtle small font-monospace">
                                                Booking: {{ strtoupper(str_replace('_', ' ', $req->booking->booking_status)) }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">
                                        {{ $req->agent ? ($req->agent->alias_name ?: $req->agent->name) : 'Agent' }}
                                    </div>
                                    <div class="text-muted small font-monospace">{{ $req->agent->email ?? '' }}</div>
                                    <div class="text-secondary font-monospace mt-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $req->assigned_at ? $req->assigned_at->format('M d, h:i A') : '' }}
                                    </div>
                                </td>
                                <td>
                                    @if($req->request_type)
                                        <div class="mb-1">
                                            <span class="badge bg-primary text-white font-monospace px-2.5 py-1">
                                                <i class="bi bi-tag-fill me-1"></i> {{ $req->request_type }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="p-2 bg-light rounded text-dark small border border-light-subtle mb-1" style="max-width: 380px; white-space: pre-wrap;">
                                        <strong class="text-primary">Description:</strong> {{ $req->change_request_text }}
                                    </div>
                                    @if($req->agent_remark)
                                        <div class="text-secondary small fst-italic mb-1">
                                            <strong>Agent Remark:</strong> {{ $req->agent_remark }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($req->paid_to_airline || $req->fop)
                                        <div class="mb-1 small font-monospace">
                                            @if($req->paid_to_airline)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1">Paid to Airline: {{ strtoupper($req->paid_to_airline) }}</span>
                                            @endif
                                            @if($req->fop)
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">FOP: {{ $req->fop }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if($req->final_remark)
                                        <div class="text-dark small fw-semibold p-2 bg-warning-subtle rounded border border-warning-subtle mb-1">
                                            <i class="bi bi-check2-circle me-1 text-warning-emphasis"></i><strong>Final Remark:</strong> {{ $req->final_remark }}
                                        </div>
                                    @endif

                                    @if($req->changes_remark)
                                        <div class="text-success small fw-semibold p-1.5 bg-success bg-opacity-10 rounded border border-success border-opacity-25 mb-1">
                                            <i class="bi bi-chat-left-dots me-1"></i>{{ $req->changes_remark }}
                                        </div>
                                    @endif

                                    @if($req->closedBy)
                                        <div class="text-muted font-monospace small" style="font-size: 0.75rem;">
                                            <i class="bi bi-person-check-fill me-1 text-primary"></i>Processed by: {{ $req->closedBy->alias_name ?: $req->closedBy->name }}
                                        </div>
                                    @endif

                                    <!-- Uploaded Attachments Loop -->
                                    @if(!empty($req->attachments_data) && count($req->attachments_data) > 0)
                                        <div class="mt-2 pt-1 border-top border-light-subtle d-flex flex-wrap gap-2">
                                            @foreach($req->attachments_data as $file)
                                                @if(($file['file_type'] ?? '') === 'image')
                                                    <a href="{{ $file['file_url'] }}" target="_blank" class="d-inline-block text-decoration-none" title="Click to view image">
                                                        <img src="{{ $file['file_url'] }}" alt="{{ $file['original_name'] }}" class="rounded border shadow-sm" style="max-height: 60px; max-width: 90px; object-fit: cover;">
                                                    </a>
                                                @else
                                                    <a href="{{ $file['file_url'] }}" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 font-monospace small d-inline-flex align-items-center gap-1 shadow-sm" title="Click to view PDF document">
                                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-6"></i>
                                                        <span>{{ $file['original_name'] ?? 'Document.pdf' }}</span>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $st = strtolower($req->status);
                                    @endphp

                                    @if($st === 'closed')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                                            Closed
                                        </span>
                                    @elseif($st === 'denied')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                                            Denied
                                        </span>
                                    @elseif($st === 'follow_up')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                            Follow Up
                                        </span>
                                    @elseif($st === 'assigned_to_agent')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff;">
                                            Assigned to Agent
                                        </span>
                                    @elseif($st === 'awaiting_revert_from_airline')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #e0f2fe; color: #075985; border: 1px solid #bae6fd;">
                                            Awaiting Revert from Airline
                                        </span>
                                    @elseif($st === 'sale_cancelled')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;">
                                            Sale Cancelled
                                        </span>
                                    @elseif($st === 'chargeback')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold text-dark" style="background-color: #fef08a; color: #854d0e; border: 1px solid #fde047;">
                                            Chargeback
                                        </span>
                                    @elseif($st === 'refunded')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold" style="background-color: #fca5a5; color: #7f1d1d; border: 1px solid #f87171;">
                                            Refunded
                                        </span>
                                    @elseif($st === 'voided')
                                        <span class="badge rounded-pill px-3 py-1.5 font-bold bg-danger text-white">
                                            Voided
                                        </span>
                                    @elseif($st === 'pending')
                                        <span class="badge bg-warning text-dark font-semibold px-3 py-1.5">
                                            <i class="bi bi-hourglass-split me-1"></i> Pending
                                        </span>
                                    @elseif($st === 'working')
                                        <span class="badge bg-info text-dark font-semibold px-3 py-1.5">
                                            <i class="bi bi-gear-fill me-1"></i> Working
                                        </span>
                                    @elseif($st === 'completed')
                                        <span class="badge bg-success text-white font-semibold px-3 py-1.5">
                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white px-3 py-1.5">
                                            {{ strtoupper(str_replace('_', ' ', $st)) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" @click="openActionModal({{ json_encode([
                                        'id' => $req->id,
                                        'booking_id' => $req->booking ? $req->booking->id : null,
                                        'booking_ref' => $req->booking ? $req->booking->booking_id : 'N/A',
                                        'airline_pnr' => $req->booking ? ($req->booking->airline_pnr ?: 'N/A') : 'N/A',
                                        'customer_name' => $req->booking ? ($req->booking->card_holder_name ?: 'N/A') : 'N/A',
                                        'agent_name' => $req->agent ? ($req->agent->alias_name ?: $req->agent->name) : 'Agent',
                                        'request_type' => $req->request_type ?: '',
                                        'status' => $req->status,
                                        'paid_to_airline' => $req->paid_to_airline ?: 'no',
                                        'fop' => $req->fop ?: '',
                                        'final_remark' => $req->final_remark ?: '',
                                        'booking_status' => $req->booking ? $req->booking->booking_status : 'booking_generated',
                                        'case_status' => $req->booking ? $req->booking->case_status : '',
                                        'request_text' => $req->change_request_text,
                                        'agent_remark' => $req->agent_remark ?: '',
                                        'changes_remark' => $req->changes_remark ?: '',
                                        'attachments_data' => $req->attachments_data ?: [],
                                    ]) }})" class="btn btn-outline-primary btn-sm fw-bold px-3 shadow-sm">
                                        <i class="bi bi-pencil-square me-1"></i> Process / Update
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-check2-all display-6 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-1 font-semibold text-dark">No change requests found</p>
                                    <small class="text-muted">When agents submit change requests for bookings, they will appear here in real-time.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-light-subtle py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-secondary">
                    Showing {{ $changeRequests->firstItem() ?? 0 }} to {{ $changeRequests->lastItem() ?? 0 }} of {{ $changeRequests->total() }} requests
                </div>
                <div>
                    {{ $changeRequests->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATE STATUS & MEDIA ACTION MODAL -->
    <div x-show="actionModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': actionModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content card bg-white border-primary shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-white border-light-subtle d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-primary"></i> Process Change Request (#<span x-text="activeReq.id"></span>)
                    </h5>
                    <button type="button" @click="actionModalOpen = false" class="btn-close"></button>
                </div>
                <form :action="`/changes/requests/${activeReq.id}/status`" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body p-4">
                        <!-- Summary info -->
                        <div class="p-3 bg-light rounded border border-light-subtle mb-4">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="text-secondary small fw-bold text-uppercase">Booking Reference</div>
                                    <div class="text-primary fw-bold h6 mb-0" x-text="`#${activeReq.booking_ref}`"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-secondary small fw-bold text-uppercase">Airline PNR</div>
                                    <div class="text-dark font-monospace fw-bold" x-text="activeReq.airline_pnr"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-secondary small fw-bold text-uppercase">Requested By (Agent)</div>
                                    <div class="text-dark fw-semibold" x-text="activeReq.agent_name"></div>
                                </div>
                                <template x-if="activeReq.request_type">
                                    <div class="col-12 mt-2">
                                        <span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1.5">
                                            <i class="bi bi-tag-fill me-1"></i> Request Type: <span x-text="activeReq.request_type"></span>
                                        </span>
                                    </div>
                                </template>
                                <div class="col-12 mt-2 pt-2 border-top border-light-subtle">
                                    <div class="text-secondary small fw-bold text-uppercase mb-1">Agent Request Details</div>
                                    <div class="text-dark small bg-white p-2.5 rounded border border-light-subtle" style="white-space: pre-wrap;" x-text="activeReq.request_text"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Selection (Dropdown with all exact options matching screenshot) -->
                        <div class="mb-4">
                            <label class="form-label text-dark small fw-bold text-uppercase d-flex justify-content-between align-items-center">
                                <span>Update Request Status <span class="text-danger">*</span></span>
                                <span class="text-muted font-monospace small" style="font-size: 0.75rem;">Closed by: {{ Auth::user()->alias_name ?: Auth::user()->name }} (Changes Desk)</span>
                            </label>
                            <select name="status" x-model="activeStatus" class="form-select form-select-lg border-primary fw-bold text-dark">
                                <option value="closed">Closed</option>
                                <option value="denied">Denied</option>
                                <option value="follow_up">Follow Up</option>
                                <option value="assigned_to_agent">Assigned to Agent</option>
                                <option value="awaiting_revert_from_airline">Awaiting Revert from Airline</option>
                                <option value="sale_cancelled">Sale Cancelled</option>
                                <option value="chargeback">Chargeback</option>
                                <option value="refunded">Refunded</option>
                                <option value="voided">Voided</option>
                                <option value="pending">Pending</option>
                                <option value="working">Working</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Payment & FOP Section (Changes Desk Input) -->
                        <div class="row g-3 mb-3 p-3 bg-light rounded border border-light-subtle">
                            <div class="col-md-6">
                                <label class="form-label text-dark small fw-bold text-uppercase mb-1">Paid to Airline?</label>
                                <div class="d-flex gap-3 mt-1">
                                    <label class="form-check-label fw-bold cursor-pointer">
                                        <input type="radio" name="paid_to_airline" value="yes" x-model="activePaidToAirline" class="form-check-input me-1"> Yes
                                    </label>
                                    <label class="form-check-label fw-bold cursor-pointer">
                                        <input type="radio" name="paid_to_airline" value="no" x-model="activePaidToAirline" class="form-check-input me-1"> No
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-dark small fw-bold text-uppercase mb-1">FOP (Form of Payment)</label>
                                <input type="text" name="fop" x-model="activeFop" placeholder="e.g. Credit Card ****1234, Voucher, Agent Balance" class="form-control bg-white border-light-subtle">
                            </div>
                        </div>

                        <!-- Final Remark (Editable Only By Changes Team) -->
                        <div class="mb-3">
                            <label class="form-label text-dark small fw-bold text-uppercase d-flex align-items-center gap-1">
                                <i class="bi bi-shield-lock-fill text-warning"></i> Final Remark (Changes Team Only)
                            </label>
                            <textarea name="final_remark" x-model="activeFinalRemark" rows="3" placeholder="Enter final resolution remark specifically recorded by the Changes Desk..." class="form-control bg-white border-warning text-dark fw-semibold"></textarea>
                            <div class="form-text text-muted small mt-1">This final remark is recorded exclusively by the Changes Team desk.</div>
                        </div>

                        <!-- Changes Team Note -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">General Changes Team Note / Instructions</label>
                            <textarea name="changes_remark" x-model="activeRemark" rows="2" placeholder="Enter additional notes or instructions for the agent..." class="form-control bg-white border-light-subtle text-dark"></textarea>
                        </div>

                        <!-- Booking Status Selection in Database -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Update Booking Table Status</label>
                                <select name="booking_status" x-model="activeBookingStatus" class="form-select border-primary-subtle text-primary fw-bold">
                                    <option value="booking_generated">Booking Generated</option>
                                    <option value="email_auth_sent">Email Auth Sent</option>
                                    <option value="email_auth_done">Email Auth Done</option>
                                    <option value="ticketed">Ticketed</option>
                                    <option value="booking_complete">Booking Complete</option>
                                    <option value="void">Void</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Case Status (If Void / Exception)</label>
                                <select name="case_status" x-model="activeCaseStatus" class="form-select border-secondary">
                                    <option value="">-- No Case Status --</option>
                                    <option value="rdr">RDR</option>
                                    <option value="retrieval">Retrieval</option>
                                    <option value="chargeback">Chargeback</option>
                                    <option value="refund">Refund</option>
                                    <option value="void">Void</option>
                                </select>
                            </div>
                        </div>

                        <!-- File Attachments Upload (PDFs, Screenshots, Media) -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase d-flex align-items-center gap-1">
                                <i class="bi bi-paperclip text-primary"></i> Attach PDF or Screenshot / Media Files
                            </label>
                            <input type="file" name="attachments[]" multiple accept=".pdf,image/*" class="form-control form-control-sm border-light-subtle">
                            <div class="form-text text-muted small"><i class="bi bi-info-circle me-1"></i> You can upload multiple PDFs or images (.png, .jpg, .jpeg, .webp). These will be visible to the agent in their Remarks History.</div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-light-subtle d-flex justify-content-end gap-2 py-3">
                        <button type="button" @click="actionModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Save &amp; Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    function changesQueueApp() {
        return {
            actionModalOpen: false,
            activeReq: {},
            activeStatus: 'pending',
            activePaidToAirline: 'no',
            activeFop: '',
            activeFinalRemark: '',
            activeBookingStatus: 'booking_generated',
            activeCaseStatus: '',
            activeRemark: '',

            openActionModal(reqData) {
                this.activeReq = reqData;
                this.activeStatus = reqData.status || 'pending';
                this.activePaidToAirline = reqData.paid_to_airline || 'no';
                this.activeFop = reqData.fop || '';
                this.activeFinalRemark = reqData.final_remark || '';
                this.activeBookingStatus = reqData.booking_status || 'booking_generated';
                this.activeCaseStatus = reqData.case_status || '';
                this.activeRemark = reqData.changes_remark || '';
                this.actionModalOpen = true;
            }
        };
    }
</script>
@endsection
