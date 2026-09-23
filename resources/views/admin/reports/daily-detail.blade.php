@extends('layouts.app')

@section('content')
<div x-data="dailyReportDetailState()" class="vstack gap-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-calendar-event text-primary"></i> Daily Bookings Detail — {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
            </h1>
            <p class="text-secondary small mb-0">Detailed breakdown of all booking transactions recorded on {{ $date }}.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reports.daily.export', ['date' => $date]) }}" class="btn btn-success btn-sm fw-bold px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-download"></i> Download CSV
            </a>
            <a href="{{ route('admin.reports.daily') }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Daily Summary
            </a>
        </div>
    </div>

    <!-- Summary Bar for the Selected Date -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3 border-end border-light-subtle text-center text-md-start">
                    <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Target Date</span>
                    <span class="h5 fw-bold text-dark font-monospace mb-0">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</span>
                </div>
                <div class="col-md-3 border-end border-light-subtle text-center text-md-start">
                    <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Total Bookings</span>
                    <span class="h5 fw-bold text-primary font-monospace mb-0">{{ number_format($summaryTotalBookings) }}</span>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Financial Totals (by Currency)</span>
                    @if(!isset($currencyBreakdown) || $currencyBreakdown->isEmpty())
                        <span class="text-muted small font-monospace">$0.00</span>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($currencyBreakdown as $cCode => $cItem)
                                <div class="px-2.5 py-1 bg-light rounded border border-light-subtle small font-monospace">
                                    <span class="badge bg-primary text-white me-1">{{ $cCode }}</span>
                                    <span class="text-secondary me-1">Total Amount:</span>
                                    <strong class="text-primary me-2">{{ number_format($cItem->total_amount, 2) }}</strong>
                                    <span class="text-secondary me-1">MCO:</span>
                                    <strong class="text-success">{{ number_format($cItem->total_mco, 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Search Query Bar -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-body p-3">
            <form action="{{ route('admin.reports.daily.detail') }}" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="col-md-9">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by Booking ID, PNR, Customer Name, Email, Phone..." class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold flex-grow-1"><i class="bi bi-search me-1"></i> Search</button>
                    @if(request('q'))
                        <a href="{{ route('admin.reports.daily.detail', ['date' => $date]) }}" class="btn btn-outline-secondary btn-sm px-3">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Detailed Bookings Table (Fields: Date, Agent, Customer detail, Service provided, Total amount, Total MCO, Booking Status, Action) -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light text-secondary small text-uppercase border-bottom">
                    <tr>
                        <th class="px-3 py-3" style="width: 130px;">1. Date</th>
                        <th class="px-3 py-3" style="width: 150px;">2. Agent</th>
                        <th class="px-3 py-3">3. Customer Detail</th>
                        <th class="px-3 py-3">4. Service Provided</th>
                        <th class="px-3 py-3" style="width: 140px;">5. Total Amount</th>
                        <th class="px-3 py-3" style="width: 130px;">6. Total MCO</th>
                        <th class="px-3 py-3" style="width: 140px;">7. Booking Status</th>
                        <th class="px-3 py-3 text-end" style="width: 160px;">8. Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <!-- 1. Date -->
                            <td class="px-3 py-3">
                                <span class="fw-bold text-dark font-monospace small d-block">
                                    {{ $booking->booking_date ? $booking->booking_date->format('Y-m-d') : $booking->created_at->format('Y-m-d') }}
                                </span>
                                <span class="text-muted small font-monospace">
                                    {{ $booking->created_at ? $booking->created_at->format('h:i A') : '' }}
                                </span>
                            </td>

                            <!-- 2. Agent -->
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark">{{ $booking->agent ? ($booking->agent->alias_name ?: $booking->agent->name) : 'N/A' }}</div>
                                <div class="small text-secondary">{{ $booking->agent ? $booking->agent->name : '' }}</div>
                            </td>

                            <!-- 3. Customer Detail -->
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark">{{ $booking->card_holder_name ?: ($booking->passenger_name ?: 'N/A') }}</div>
                                <div class="small text-secondary"><i class="bi bi-envelope me-1"></i>{{ $booking->email_address }}</div>
                                @if($booking->phone)
                                    <div class="small text-secondary"><i class="bi bi-telephone me-1"></i>{{ $booking->phone }}</div>
                                @endif
                            </td>

                            <!-- 4. Service Provided -->
                            <td class="px-3 py-3">
                                <span class="badge bg-light text-dark border border-secondary-subtle text-capitalize px-2 py-1 me-1">
                                    {{ str_replace('_', ' ', ucfirst($booking->service_provided ?: 'Flight Booking')) }}
                                </span>
                                <span class="fw-bold text-primary font-monospace small d-block mt-1">
                                    PNR: {{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}
                                </span>
                                <span class="small text-secondary d-block">
                                    Portal: {{ $booking->booking_portal ?: 'N/A' }}
                                </span>
                            </td>

                            <!-- 5. Total Amount -->
                            <td class="px-3 py-3 font-monospace fw-bold text-primary">
                                {{ $booking->currency ?: 'USD' }} {{ number_format($booking->total_amount, 2) }}
                            </td>

                            <!-- 6. Total MCO -->
                            <td class="px-3 py-3 font-monospace fw-bold text-success">
                                {{ $booking->currency ?: 'USD' }} {{ number_format($booking->reportable_mco, 2) }}
                            </td>

                            <!-- 7. Booking Status -->
                            <td class="px-3 py-3">
                                @php
                                    $bStatusBadge = match($booking->booking_status) {
                                        'booking_generated' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'email_auth_done' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'ticketed' => 'bg-secondary text-white',
                                        'booking_complete' => 'bg-success-subtle text-success border border-success-subtle',
                                        'void' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'failed' => 'bg-danger text-white border border-danger',
                                        default => 'bg-light text-dark border border-secondary-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $bStatusBadge }} text-capitalize px-2.5 py-1">
                                    {{ str_replace('_', ' ', ucfirst($booking->booking_status)) }}
                                </span>
                            </td>

                            <!-- 8. Action - View, Add Remark -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- View Button -->
                                    <button type="button" 
                                            onclick="showBookingDetailModal({{ $booking->id }})"
                                            class="btn btn-outline-primary btn-sm px-2 py-1" 
                                            title="View Complete Booking Details">
                                        <i class="bi bi-eye me-1"></i> View
                                    </button>

                                    <!-- Add Remark Button -->
                                    <button type="button" 
                                            @click="openRemarkModal({{ $booking->id }}, '{{ $booking->booking_id }}')" 
                                            class="btn btn-outline-success btn-sm px-2 py-1" 
                                            title="Add Admin Remark">
                                        <i class="bi bi-chat-left-text me-1"></i> Add Remark
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-5 text-center text-secondary fst-italic">
                                No bookings recorded on {{ $date }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <!-- VIEW BOOKING MODAL -->
    <div x-show="viewModalOpen" x-cloak class="modal fade" :class="{ 'show d-block': viewModalOpen }" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-white border-light-subtle shadow-lg">
                <div class="modal-header border-light-subtle py-3 bg-light">
                    <h5 class="modal-title font-bold h6 text-uppercase text-primary d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text"></i> Booking Overview (#<span x-text="selectedBooking.booking_id"></span>)
                    </h5>
                    <button type="button" class="btn-close" @click="viewModalOpen = false"></button>
                </div>
                <div class="modal-body p-4 vstack gap-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Booking Reference</span>
                            <span class="fs-6 font-monospace fw-bold text-dark" x-text="`#${selectedBooking.booking_id}`"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Airline / PNR</span>
                            <span class="fs-6 font-monospace fw-bold text-primary" x-text="selectedBooking.pnr"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Booking Agent</span>
                            <span class="fw-semibold text-dark" x-text="selectedBooking.agent"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Booking Date</span>
                            <span class="fw-semibold text-dark font-monospace" x-text="selectedBooking.date"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Customer Name</span>
                            <span class="fw-semibold text-dark" x-text="selectedBooking.customer_name"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Customer Email</span>
                            <span class="fw-semibold text-dark" x-text="selectedBooking.customer_email"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Service Provided</span>
                            <span class="badge bg-light text-dark border border-secondary-subtle px-2 py-1 text-uppercase" x-text="selectedBooking.service"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Booking Status</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 text-uppercase" x-text="selectedBooking.status"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Total Amount</span>
                            <span class="fs-6 font-monospace fw-bold text-primary" x-text="selectedBooking.amount"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary small text-uppercase fw-bold d-block">Total MCO</span>
                            <span class="fs-6 font-monospace fw-bold text-success" x-text="selectedBooking.mco"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-light-subtle bg-light">
                    <a :href="selectedBooking.edit_url" class="btn btn-outline-info btn-sm fw-bold">
                        <i class="bi bi-pencil-square me-1"></i> Edit Full Record
                    </a>
                    <button type="button" @click="viewModalOpen = false" class="btn btn-secondary btn-sm fw-semibold">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD REMARK MODAL -->
    <div x-show="remarkModalOpen" x-cloak class="modal fade" :class="{ 'show d-block': remarkModalOpen }" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-white border-light-subtle shadow-lg">
                <div class="modal-header border-light-subtle py-3">
                    <h5 class="modal-title font-bold h6 text-uppercase text-success d-flex align-items-center gap-2">
                        <i class="bi bi-chat-left-text-fill"></i> Add Admin Remark (#<span x-text="remarkBookingRef"></span>)
                    </h5>
                    <button type="button" class="btn-close" @click="remarkModalOpen = false"></button>
                </div>
                <form :action="`/admin/bookings/${remarkBookingId}/add-admin-remark`" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Remark Note <span class="text-danger">*</span></label>
                            <textarea name="remark" rows="4" required placeholder="Enter administrative remark or note for this booking..." class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-light-subtle">
                        <button type="button" @click="remarkModalOpen = false" class="btn btn-outline-secondary btn-sm fw-semibold">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="bi bi-check-lg me-1"></i> Log Remark</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function dailyReportDetailState() {
        return {
            viewModalOpen: false,
            selectedBooking: {},
            remarkModalOpen: false,
            remarkBookingId: null,
            remarkBookingRef: '',

            openViewModal(booking) {
                this.selectedBooking = booking;
                this.viewModalOpen = true;
            },

            openRemarkModal(id, ref) {
                this.remarkBookingId = id;
                this.remarkBookingRef = ref;
                this.remarkModalOpen = true;
            }
        };
    }
</script>
@endsection
