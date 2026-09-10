@extends('layouts.app')

@section('content')
<div class="vstack gap-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-bar-graph text-success"></i> Daily Booking Reports
            </h1>
            <p class="text-secondary small mb-0">Date-wise breakdown of total bookings, revenue, and MCO by currency.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reports.daily.export', request()->query()) }}" class="btn btn-success btn-sm fw-bold px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export Range to CSV
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Date Range Filter Bar -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-body p-3">
            <form action="{{ route('admin.reports.daily') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold px-3 flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter Date Range
                    </button>
                    <a href="{{ route('admin.reports.daily') }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Daily Report Summary Table -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary"></i> Daily Bookings Summary
            </h2>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-3 py-1">
                Showing {{ $dailyDates->total() }} Recorded Days
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light text-secondary small text-uppercase border-bottom">
                    <tr>
                        <th class="px-3 py-3" style="width: 160px;">Date</th>
                        <th class="px-3 py-3 text-center" style="width: 140px;">Total Bookings</th>
                        <th class="px-3 py-3">Total Amount (by Currency)</th>
                        <th class="px-3 py-3">Total MCO (by Currency)</th>
                        <th class="px-3 py-3 text-end" style="width: 220px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyDates as $row)
                        @php
                            $dateObj = \Carbon\Carbon::parse($row->report_date);
                            $currencies = $currencyBreakdowns[$row->report_date] ?? [];
                        @endphp
                        <tr>
                            <!-- 1. Date -->
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark fs-6">{{ $dateObj->format('M d, Y') }}</div>
                                <span class="small text-secondary font-monospace">{{ $row->report_date }}</span>
                            </td>

                            <!-- 2. Total Bookings -->
                            <td class="px-3 py-3 text-center">
                                <span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1.5 rounded-pill shadow-sm">
                                    {{ number_format($row->total_bookings) }}
                                </span>
                            </td>

                            <!-- 3. Total Amount - (by Currency) -->
                            <td class="px-3 py-3">
                                @if(empty($currencies))
                                    <span class="text-muted small">$0.00</span>
                                @else
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($currencies as $cItem)
                                            <div class="px-2.5 py-1 bg-light rounded border border-light-subtle small font-monospace">
                                                <span class="badge bg-primary-subtle text-primary me-1">{{ $cItem['currency'] }}</span>
                                                <strong class="text-primary">{{ number_format($cItem['total_amount'], 2) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <!-- 4. Total MCO - (by Currency) -->
                            <td class="px-3 py-3">
                                @if(empty($currencies))
                                    <span class="text-muted small">$0.00</span>
                                @else
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($currencies as $cItem)
                                            <div class="px-2.5 py-1 bg-light rounded border border-light-subtle small font-monospace">
                                                <span class="badge bg-success-subtle text-success me-1">{{ $cItem['currency'] }}</span>
                                                <strong class="text-success">{{ number_format($cItem['total_mco'], 2) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <!-- 5. Action - View, Download CSV -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-inline-flex gap-1.5">
                                    <a href="{{ route('admin.reports.daily.detail', ['date' => $row->report_date]) }}" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-semibold" title="View detailed bookings for this date">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                    <a href="{{ route('admin.reports.daily.export', ['date' => $row->report_date]) }}" class="btn btn-outline-success btn-sm px-2.5 py-1 fw-semibold" title="Download CSV report for this date">
                                        <i class="bi bi-download me-1"></i> Download CSV
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-5 text-center text-secondary fst-italic">
                                <i class="bi bi-info-circle me-1 fs-5 d-block mb-1"></i>
                                No booking reports found for the selected date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dailyDates->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                {{ $dailyDates->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
