@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" x-data="callLogsApp()">

    <!-- Header & Action Row -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h4 font-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-telephone-outbound-fill text-primary"></i> Call Logs Management
            </h1>
            <p class="text-secondary small mb-0">Record, filter, search, and manage customer interaction call logs.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" @click="downloadCsv()" class="btn btn-outline-success btn-sm fw-bold px-3 d-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span>Download CSV</span>
                <span x-show="selectedIds.length > 0" class="badge bg-success text-white ms-1" x-text="`(${selectedIds.length})`"></span>
            </button>
            <button type="button" @click="openAddModal()" class="btn btn-primary btn-sm fw-bold px-3.5 d-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-plus-lg"></i>
                <span>Log New Call</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border-success mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('call-logs.index') }}" class="row g-2 align-items-center">
                <!-- Search Keyword -->
                <div class="col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer, phone, email, city..." class="form-control form-control-sm border-start-0 ps-0">
                    </div>
                </div>

                <!-- Call Type (Service Provided) Filter -->
                <div class="col-md-2">
                    <select name="service_provided" class="form-select form-select-sm">
                        <option value="">-- All Call Types --</option>
                        <option value="new_booking" {{ request('service_provided') === 'new_booking' ? 'selected' : '' }}>New Booking</option>
                        <option value="exchange" {{ request('service_provided') === 'exchange' ? 'selected' : '' }}>Exchange</option>
                        <option value="cancellation" {{ request('service_provided') === 'cancellation' ? 'selected' : '' }}>Cancellation</option>
                        <option value="refund" {{ request('service_provided') === 'refund' ? 'selected' : '' }}>Refund</option>
                        <option value="seat_selection" {{ request('service_provided') === 'seat_selection' ? 'selected' : '' }}>Seat Selection</option>
                        <option value="baggage_addition" {{ request('service_provided') === 'baggage_addition' ? 'selected' : '' }}>Baggage Edition</option>
                        <option value="others" {{ request('service_provided') === 'others' ? 'selected' : '' }}>Others</option>
                        <option value="cancel_and_refund" {{ request('service_provided') === 'cancel_and_refund' ? 'selected' : '' }}>Cancel and Refund</option>
                        <option value="name_correction" {{ request('service_provided') === 'name_correction' ? 'selected' : '' }}>Name Correction</option>
                        <option value="flight_upgrade" {{ request('service_provided') === 'flight_upgrade' ? 'selected' : '' }}>Flight Upgrade</option>
                        <option value="dob_correction" {{ request('service_provided') === 'dob_correction' ? 'selected' : '' }}>D.O.B Correction</option>
                        <option value="pet_in_cabin" {{ request('service_provided') === 'pet_in_cabin' ? 'selected' : '' }}>Pet In Cabin</option>
                        <option value="ancillary_refund" {{ request('service_provided') === 'ancillary_refund' ? 'selected' : '' }}>Ancillary Refund</option>
                        <option value="general_inquiry" {{ request('service_provided') === 'general_inquiry' ? 'selected' : '' }}>General Inquiry</option>
                        <option value="infant_ticket" {{ request('service_provided') === 'infant_ticket' ? 'selected' : '' }}>Infant Ticket</option>
                    </select>
                </div>

                <!-- Single Date -->
                <div class="col-md-1">
                    <input type="date" name="date" value="{{ request('date') }}" title="Specific Call Date" class="form-control form-control-sm" placeholder="Specific Date">
                </div>

                <!-- Start Date -->
                <div class="col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary small border-end-0">From</span>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm border-start-0 ps-1">
                    </div>
                </div>

                <!-- End Date -->
                <div class="col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary small border-end-0">To</span>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm border-start-0 ps-1">
                    </div>
                </div>

                <!-- Follow-up Filter -->
                <div class="col-md-1">
                    <select name="follow_up" class="form-select form-select-sm">
                        <option value="">-- Follow-Up --</option>
                        <option value="1" {{ request('follow_up') === '1' ? 'selected' : '' }}>Yes (Required)</option>
                        <option value="0" {{ request('follow_up') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <!-- Agent Filter (Admin / Master Admin / Manager Only) -->
                @if(Auth::user()->hasAnyRole(['admin', 'master_admin', 'manager']) || in_array(Auth::user()->role, ['admin', 'master_admin', 'manager']))
                <div class="col-md-2">
                    <select name="agent_id" class="form-select form-select-sm">
                        <option value="">-- All Agents --</option>
                        @foreach($agents as $agt)
                            <option value="{{ $agt->id }}" {{ request('agent_id') == $agt->id ? 'selected' : '' }}>
                                {{ $agt->alias_name ?: $agt->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Sorting Option -->
                <div class="col-md-2">
                    <select name="sort" class="form-select form-select-sm">
                        <option value="call_date_desc" {{ request('sort', 'call_date_desc') === 'call_date_desc' ? 'selected' : '' }}>Sort: Latest Call Date</option>
                        <option value="call_date_asc" {{ request('sort') === 'call_date_asc' ? 'selected' : '' }}>Sort: Oldest Call Date</option>
                        <option value="customer_name_asc" {{ request('sort') === 'customer_name_asc' ? 'selected' : '' }}>Sort: Customer Name (A-Z)</option>
                        <option value="customer_name_desc" {{ request('sort') === 'customer_name_desc' ? 'selected' : '' }}>Sort: Customer Name (Z-A)</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="col-md-auto d-flex align-items-center gap-1.5 ms-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'service_provided', 'date', 'start_date', 'end_date', 'follow_up', 'agent_id', 'sort']))
                        <a href="{{ route('call-logs.index') }}" class="btn btn-outline-secondary btn-sm px-2.5">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Call Logs Table Card -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-list-stars text-primary"></i> Call Records Listing
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">{{ $callLogs->total() }} total</span>
            </h2>
            <div x-show="selectedIds.length > 0" class="small text-primary fw-semibold" x-cloak>
                <i class="bi bi-check2-square me-1"></i> <span x-text="selectedIds.length"></span> call logs selected for batch export
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-secondary border-bottom">
                            <th style="width: 45px;" class="text-center">
                                <input type="checkbox" @change="toggleSelectAll($event)" class="form-check-input">
                            </th>
                            <th style="width: 70px;">ID</th>
                            <th>Call Date &amp; Time</th>
                            <th>Customer Name</th>
                            <th>Phone Number</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>Call Type</th>
                            @if(Auth::user()->hasAnyRole(['admin', 'master_admin', 'manager']) || in_array(Auth::user()->role, ['admin', 'master_admin', 'manager']))
                                <th>Agent</th>
                            @endif
                            <th style="width: 110px;" class="text-center">Follow-Up</th>
                            <th style="width: 140px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($callLogs as $log)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" value="{{ $log->id }}" x-model="selectedIds" class="form-check-input">
                                </td>
                                <td class="font-monospace text-secondary">#{{ $log->id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark small">
                                        <i class="bi bi-calendar-event text-primary me-1"></i>
                                        {{ $log->call_date ? $log->call_date->format('M d, Y') : 'N/A' }}
                                    </div>
                                    <div class="text-secondary font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $log->call_date ? $log->call_date->format('h:i A') : '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $log->customer_name }}</div>
                                    @if($log->remark)
                                        <div class="text-muted text-truncate small" style="max-width: 200px;" title="{{ $log->remark }}">
                                            <i class="bi bi-chat-left-text me-1"></i>{{ $log->remark }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <a href="tel:{{ $log->phone_number }}" class="text-primary text-decoration-none font-monospace small fw-semibold">
                                        <i class="bi bi-telephone me-1"></i>{{ $log->phone_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($log->email)
                                        <a href="mailto:{{ $log->email }}" class="text-secondary text-decoration-none small">
                                            <i class="bi bi-envelope me-1"></i>{{ $log->email }}
                                        </a>
                                    @else
                                        <span class="text-secondary fst-italic small">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-dark small">{{ $log->city ?: 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle px-2.5 py-1 small fw-semibold" style="background-color: #e0e7ff; color: #4338ca;">
                                        <i class="bi bi-tag-fill me-1"></i>{{ $log->service_provided_label }}
                                    </span>
                                </td>
                                @if(Auth::user()->hasAnyRole(['admin', 'master_admin', 'manager']) || in_array(Auth::user()->role, ['admin', 'master_admin', 'manager']))
                                    <td>
                                        <span class="badge bg-light text-secondary border border-secondary-subtle small">
                                            <i class="bi bi-person-circle me-1"></i>{{ $log->agent ? ($log->agent->alias_name ?: $log->agent->name) : 'N/A' }}
                                        </span>
                                    </td>
                                @endif
                                <td class="text-center">
                                    @if($log->follow_up)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-semibold px-2.5 py-1">
                                            <i class="bi bi-bell-fill me-1"></i> Yes
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border border-secondary-subtle px-2.5 py-1">
                                            No
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" @click="viewCallLog({{ $log->id }})" class="btn btn-outline-primary" title="View Call Details">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        @if(Auth::user()->hasAnyRole(['admin', 'master_admin', 'manager']) || in_array(Auth::user()->role, ['admin', 'master_admin', 'manager']) || $log->agent_id === Auth::id())
                                            <button type="button" @click="confirmDelete({{ $log->id }}, '{{ addslashes($log->customer_name) }}')" class="btn btn-outline-danger" title="Delete Call Log">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-secondary">
                                    <i class="bi bi-telephone-x display-6 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-1 font-semibold text-dark">No call logs found</p>
                                    <small>Try adjusting your search query or filters, or log a new call entry.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top border-light-subtle py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-secondary">
                    Showing {{ $callLogs->firstItem() ?? 0 }} to {{ $callLogs->lastItem() ?? 0 }} of {{ $callLogs->total() }} call records
                </div>
                <div>
                    {{ $callLogs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- LOG NEW CALL MODAL -->
    <div x-show="addModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': addModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content card bg-white border-0 shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-white border-bottom border-light-subtle d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-telephone-plus text-primary"></i> Log Customer Call Entry
                    </h5>
                    <button type="button" @click="addModalOpen = false" class="btn-close"></button>
                </div>
                <form action="{{ route('call-logs.store') }}" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Customer Name -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" required placeholder="John Doe" class="form-control">
                            </div>

                            <!-- Phone Number -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" required placeholder="+1 555-019-2831" class="form-control font-monospace">
                            </div>

                            <!-- Customer Email -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Email Address</label>
                                <input type="email" name="email" placeholder="customer@example.com" class="form-control">
                            </div>

                            <!-- City -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">City</label>
                                <input type="text" name="city" placeholder="New York" class="form-control">
                            </div>

                            <!-- Call Type (Service Provided) -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Call Type <span class="text-danger">*</span></label>
                                <select name="service_provided" x-model="formData.service_provided" required class="form-select">
                                    <option value="new_booking">New Booking</option>
                                    <option value="exchange">Exchange</option>
                                    <option value="cancellation">Cancellation</option>
                                    <option value="refund">Refund</option>
                                    <option value="seat_selection">Seat Selection</option>
                                    <option value="baggage_addition">Baggage Edition</option>
                                    <option value="others">Others</option>
                                    <option value="cancel_and_refund">Cancel and Refund</option>
                                    <option value="name_correction">Name Correction</option>
                                    <option value="flight_upgrade">Flight Upgrade</option>
                                    <option value="dob_correction">D.O.B Correction</option>
                                    <option value="pet_in_cabin">Pet In Cabin</option>
                                    <option value="ancillary_refund">Ancillary Refund</option>
                                    <option value="general_inquiry">General Inquiry</option>
                                    <option value="infant_ticket">Infant Ticket</option>
                                </select>
                            </div>

                            <!-- Call Date & Time -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Call Date &amp; Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="call_date" x-model="newCallDate" required class="form-control">
                            </div>

                            <!-- Follow-up Radio -->
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Follow-Up Required? <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-4 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="follow_up" id="fu_yes" value="1">
                                        <label class="form-check-label text-warning-emphasis font-semibold" for="fu_yes">
                                            <i class="bi bi-bell-fill me-1"></i> Yes (Requires Follow-up)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="follow_up" id="fu_no" value="0" checked>
                                        <label class="form-check-label text-dark" for="fu_no">
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Remark -->
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Call Remarks / Notes</label>
                                <textarea name="remark" rows="3" placeholder="Enter notes about customer inquiry, flight options provided, or follow-up details..." class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top border-light-subtle d-flex justify-content-end gap-2 py-3">
                        <button type="button" @click="addModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="bi bi-check-circle me-1"></i> Save Call Log
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- VIEW CALL LOG MODAL -->
    <div x-show="viewModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': viewModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card bg-white border-0 shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-white border-bottom border-light-subtle d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary"></i> Call Log Details (#<span x-text="viewData.id"></span>)
                    </h5>
                    <button type="button" @click="viewModalOpen = false" class="btn-close"></button>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Customer Name</div>
                            <div class="text-dark fw-bold h6 mb-0" x-text="viewData.customer_name"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Phone Number</div>
                            <div class="text-primary font-monospace fw-semibold" x-text="viewData.phone_number"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Email</div>
                            <div class="text-dark" x-text="viewData.email"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">City</div>
                            <div class="text-dark" x-text="viewData.city"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Call Type</div>
                            <div>
                                <span class="badge border fw-semibold" style="background-color: #e0e7ff; color: #4338ca; border-color: #c7d2fe;" x-text="viewData.service_provided_label"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Call Date &amp; Time</div>
                            <div class="text-primary font-monospace fw-semibold" x-text="viewData.call_date"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Follow-Up Required</div>
                            <div>
                                <span class="badge" :class="viewData.follow_up === 'Yes' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-secondary border border-secondary-subtle'" x-text="viewData.follow_up"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small fw-bold text-uppercase">Agent</div>
                            <div class="text-dark fw-semibold" x-text="viewData.agent_name"></div>
                        </div>
                        <div class="col-12 border-top border-light-subtle pt-3">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">Remarks / Conversation Notes</div>
                            <div class="p-3 bg-light rounded text-dark small border border-light-subtle" style="white-space: pre-wrap;" x-text="viewData.remark"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-top border-light-subtle d-flex justify-content-end py-3">
                    <button type="button" @click="viewModalOpen = false" class="btn btn-outline-secondary btn-sm px-4">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Delete Action -->
    <form id="deleteCallLogForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

</div>

<script>
    function callLogsApp() {
        return {
            selectedIds: [],
            addModalOpen: false,
            viewModalOpen: false,
            newCallDate: '',
            formData: {
                service_provided: 'new_booking'
            },
            viewData: {
                id: '',
                customer_name: '',
                phone_number: '',
                email: '',
                city: '',
                service_provided: '',
                service_provided_label: '',
                follow_up: '',
                call_date: '',
                remark: '',
                agent_name: ''
            },

            openAddModal() {
                // Set default datetime to now
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                this.newCallDate = now.toISOString().slice(0, 16);
                this.formData.service_provided = 'new_booking';
                this.addModalOpen = true;
            },

            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedIds = [
                        @foreach($callLogs as $log)
                            {{ $log->id }},
                        @endforeach
                    ];
                } else {
                    this.selectedIds = [];
                }
            },

            viewCallLog(id) {
                fetch(`/call-logs/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.viewData = data.data;
                        this.viewModalOpen = true;
                    }
                })
                .catch(err => {
                    console.error('Error fetching call log details:', err);
                });
            },

            confirmDelete(id, customerName) {
                Swal.fire({
                    title: 'Delete Call Log?',
                    text: `Are you sure you want to delete call record for "${customerName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete',
                    background: '#ffffff',
                    color: '#1e293b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteCallLogForm');
                        form.action = `/call-logs/${id}`;
                        form.submit();
                    }
                });
            },

            downloadCsv() {
                let url = "{{ route('call-logs.export') }}";
                let params = new URLSearchParams();

                if (this.selectedIds.length > 0) {
                    params.append('ids', this.selectedIds.join(','));
                } else {
                    // Pass current query string filters
                    const currentParams = new URLSearchParams(window.location.search);
                    currentParams.forEach((val, key) => {
                        params.append(key, val);
                    });
                }

                const finalUrl = url + '?' + params.toString();
                window.location.href = finalUrl;
            }
        };
    }
</script>
@endsection