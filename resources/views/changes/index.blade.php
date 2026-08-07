@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" x-data="changesQueueApp()">

    <!-- Header Row -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h4 font-bold text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat text-info"></i> Changes Queue Panel
            </h1>
            <p class="text-secondary small mb-0">Manage, process, and complete agent booking change requests.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
                <i class="bi bi-clock-history me-1"></i> Real-time Queue
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border-success mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('changes.index') }}" class="row g-2 align-items-center">
                <!-- Search Keyword -->
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body-tertiary border-secondary text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search booking ref, customer, request text..." class="form-control bg-dark border-secondary text-white">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm bg-dark border-secondary text-white">
                        <option value="">-- All Request Statuses --</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending (Awaiting Action)</option>
                        <option value="working" {{ request('status') === 'working' ? 'selected' : '' }}>Working (In Progress)</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div class="col-md-3">
                    <input type="date" name="date" value="{{ request('date') }}" title="Filter by Assigned Date" class="form-control form-control-sm bg-dark border-secondary text-white">
                </div>

                <!-- Actions -->
                <div class="col-md-2 d-flex align-items-center gap-1.5 ms-auto">
                    <button type="submit" class="btn btn-info btn-sm px-3 fw-bold text-dark w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'date']))
                        <a href="{{ route('changes.index') }}" class="btn btn-outline-secondary btn-sm px-2.5">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Listing Table Card -->
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-list-task text-info"></i> Change Requests Listing
                <span class="badge bg-info-subtle text-info border border-info-subtle ms-2">{{ $changeRequests->total() }} total</span>
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-uppercase text-secondary border-secondary">
                            <th style="width: 70px;">Req #</th>
                            <th>Booking Ref</th>
                            <th>Requested By</th>
                            <th>Assigned Date &amp; Time</th>
                            <th>Change Request Details</th>
                            <th style="width: 120px;" class="text-center">Status</th>
                            <th style="width: 140px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeRequests as $req)
                            <tr>
                                <td class="font-monospace text-secondary">#{{ $req->id }}</td>
                                <td>
                                    <div class="fw-bold text-white">
                                        <i class="bi bi-journal-text text-primary me-1"></i>
                                        #{{ $req->booking ? $req->booking->booking_id : 'N/A' }}
                                    </div>
                                    @if($req->booking && $req->booking->card_holder_name)
                                        <div class="text-secondary small">{{ $req->booking->card_holder_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-white small">
                                        {{ $req->agent ? ($req->agent->alias_name ?: $req->agent->name) : 'Agent' }}
                                    </div>
                                    <div class="text-secondary small font-monospace">{{ $req->agent->email ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-slate-200 small">
                                        <i class="bi bi-calendar-event me-1 text-info"></i>
                                        {{ $req->assigned_at ? $req->assigned_at->format('M d, Y') : 'N/A' }}
                                    </div>
                                    <div class="text-secondary font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $req->assigned_at ? $req->assigned_at->format('h:i A') : '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="p-2 bg-body-tertiary rounded text-white small border border-secondary mb-1" style="max-width: 380px; white-space: pre-wrap;">
                                        {{ $req->change_request_text }}
                                    </div>
                                    @if($req->agent_remark)
                                        <div class="text-secondary small italic">
                                            <strong>Agent Remark:</strong> {{ $req->agent_remark }}
                                        </div>
                                    @endif
                                    @if($req->changes_remark)
                                        <div class="text-info small fw-semibold mt-1">
                                            <i class="bi bi-chat-left-dots-fill me-1"></i><strong>Changes Remark:</strong> {{ $req->changes_remark }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($req->status === 'pending')
                                        <span class="badge bg-warning text-dark font-semibold px-2.5 py-1">
                                            <i class="bi bi-hourglass-split me-1"></i> Pending
                                        </span>
                                    @elseif($req->status === 'working')
                                        <span class="badge bg-info text-dark font-semibold px-2.5 py-1">
                                            <i class="bi bi-gear-fill me-1"></i> Working
                                        </span>
                                    @elseif($req->status === 'completed')
                                        <span class="badge bg-success text-white font-semibold px-2.5 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white px-2.5 py-1">
                                            Cancelled
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" @click="openActionModal({{ json_encode([
                                        'id' => $req->id,
                                        'booking_ref' => $req->booking ? $req->booking->booking_id : 'N/A',
                                        'agent_name' => $req->agent ? ($req->agent->alias_name ?: $req->agent->name) : 'Agent',
                                        'status' => $req->status,
                                        'request_text' => $req->change_request_text,
                                        'agent_remark' => $req->agent_remark ?: '',
                                        'changes_remark' => $req->changes_remark ?: '',
                                    ]) }})" class="btn btn-outline-info btn-sm fw-bold px-3">
                                        <i class="bi bi-pencil-square me-1"></i> Action
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-check2-all display-6 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-1 font-semibold text-white">No change requests found</p>
                                    <small>When agents submit change requests for bookings, they will appear here in real-time.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-dark border-secondary py-3">
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

    <!-- UPDATE STATUS ACTION MODAL -->
    <div x-show="actionModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': actionModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content card bg-dark border-info shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-dark border-info d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-white fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-info"></i> Update Change Request (#<span x-text="activeReq.id"></span>)
                    </h5>
                    <button type="button" @click="actionModalOpen = false" class="btn-close btn-close-white"></button>
                </div>
                <form :action="`/changes/requests/${activeReq.id}/status`" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <!-- Summary info -->
                        <div class="p-3 bg-body-tertiary rounded border border-secondary mb-4">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="text-secondary small fw-bold text-uppercase">Booking Reference</div>
                                    <div class="text-white fw-bold h6 mb-0" x-text="`#${activeReq.booking_ref}`"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-secondary small fw-bold text-uppercase">Requested By (Agent)</div>
                                    <div class="text-info fw-semibold" x-text="activeReq.agent_name"></div>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top border-secondary">
                                    <div class="text-secondary small fw-bold text-uppercase mb-1">Agent Request Details</div>
                                    <div class="text-white small" style="white-space: pre-wrap;" x-text="activeReq.request_text"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Selection -->
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Update Request Status <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="p-3 border rounded border-secondary w-100 cursor-pointer text-center" :class="{ 'border-warning bg-warning-subtle text-dark font-bold': activeStatus === 'pending', 'bg-dark text-white': activeStatus !== 'pending' }">
                                        <input type="radio" name="status" value="pending" x-model="activeStatus" class="form-check-input me-2">
                                        <span class="small fw-bold">Pending</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="p-3 border rounded border-secondary w-100 cursor-pointer text-center" :class="{ 'border-info bg-info-subtle text-dark font-bold': activeStatus === 'working', 'bg-dark text-white': activeStatus !== 'working' }">
                                        <input type="radio" name="status" value="working" x-model="activeStatus" class="form-check-input me-2">
                                        <span class="small fw-bold">Working (Email Agent)</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="p-3 border rounded border-secondary w-100 cursor-pointer text-center" :class="{ 'border-success bg-success-subtle text-dark font-bold': activeStatus === 'completed', 'bg-dark text-white': activeStatus !== 'completed' }">
                                        <input type="radio" name="status" value="completed" x-model="activeStatus" class="form-check-input me-2">
                                        <span class="small fw-bold">Completed (Email Agent)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Changes Team Remark -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Changes Team Remarks / Resolution Notes</label>
                            <textarea name="changes_remark" x-model="activeRemark" rows="3" placeholder="Enter remarks for the agent regarding progress or completed changes..." class="form-control bg-dark border-secondary text-white"></textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-dark border-secondary d-flex justify-content-end gap-2 py-3">
                        <button type="button" @click="actionModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                        <button type="submit" class="btn btn-info btn-sm px-4 fw-bold text-dark">
                            <i class="bi bi-save me-1"></i> Save &amp; Update Status
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
            activeRemark: '',

            openActionModal(reqData) {
                this.activeReq = reqData;
                this.activeStatus = reqData.status;
                this.activeRemark = reqData.changes_remark;
                this.actionModalOpen = true;
            }
        };
    }
</script>
@endsection
