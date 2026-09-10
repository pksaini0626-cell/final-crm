@extends('layouts.app')

@section('content')
<div class="vstack gap-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i> Executive Admin Dashboard
            </h1>
            <p class="text-secondary small mb-0">System performance, live agent activity, recent bookings, and revenue breakdowns.</p>
            <code>Current Server time : {{ now()->format('H:i:s') }}</code>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-secondary border border-light-subtle px-3 py-2 fw-semibold shadow-sm">
                <i class="bi bi-calendar3 me-1 text-primary"></i> {{ now()->format('l, M j, Y') }}
            </span>
            <a href="{{ route('admin.reports.daily') }}" class="btn btn-success btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Daily Report
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="bi bi-shield-lock me-1"></i> Admin Control
            </a>
            <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> New Booking
            </a>
        </div>
    </div>

    <!-- SECTION 1: TOP KPI CARDS (Today's Bookings vs This Month's Bookings) -->
    <div class="row g-3">
        <!-- Today's Performance Group -->
        <div class="col-lg-6">
            <div class="card bg-white border-primary-subtle shadow-sm h-100">
                <div class="card-header bg-primary-subtle border-primary-subtle py-2.5 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-primary mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-sun-fill"></i> Today's Performance
                    </h2>
                    <span class="badge bg-primary text-white font-monospace small px-2 py-1">TODAY</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-3 text-center border-end border-light-subtle">
                            <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Bookings</span>
                            <span class="h4 fw-bold text-dark font-monospace mb-0">{{ number_format($todayBookingsCount) }}</span>
                        </div>
                        <div class="col-9">
                            @if(!isset($todayCurrencyBreakdown) || $todayCurrencyBreakdown->isEmpty())
                                <div class="row text-center">
                                    <div class="col-6 border-end border-light-subtle">
                                        <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Total Amount</span>
                                        <span class="h6 fw-bold text-primary font-monospace mb-0">$0.00</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Total MCO</span>
                                        <span class="h6 fw-bold text-success font-monospace mb-0">$0.00</span>
                                    </div>
                                </div>
                            @else
                                <div class="vstack gap-1">
                                    @foreach($todayCurrencyBreakdown as $cCode => $cItem)
                                        <div class="d-flex justify-content-between align-items-center px-2.5 py-1 bg-light rounded border border-light-subtle small font-monospace">
                                            <span class="badge bg-primary text-white font-monospace">{{ $cCode }}</span>
                                            <div class="d-flex gap-3">
                                                <div>
                                                    <span class="text-secondary small me-1">Amount:</span>
                                                    <strong class="text-primary">{{ $cCode }} {{ number_format($cItem->total_amount, 2) }}</strong>
                                                </div>
                                                <div>
                                                    <span class="text-secondary small me-1">MCO:</span>
                                                    <strong class="text-success">{{ $cCode }} {{ number_format($cItem->total_mco, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month's Performance Group -->
        <div class="col-lg-6">
            <div class="card bg-white border-success-subtle shadow-sm h-100">
                <div class="card-header bg-success-subtle border-success-subtle py-2.5 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-success-emphasis mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check-fill"></i> This Month's Performance
                    </h2>
                    <span class="badge bg-success text-white font-monospace small px-2 py-1">{{ strtoupper(now()->format('M Y')) }}</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-3 text-center border-end border-light-subtle">
                            <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Bookings</span>
                            <span class="h4 fw-bold text-dark font-monospace mb-0">{{ number_format($monthBookingsCount) }}</span>
                        </div>
                        <div class="col-9">
                            @if(!isset($monthCurrencyBreakdown) || $monthCurrencyBreakdown->isEmpty())
                                <div class="row text-center">
                                    <div class="col-6 border-end border-light-subtle">
                                        <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Total Amount</span>
                                        <span class="h6 fw-bold text-primary font-monospace mb-0">$0.00</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Total MCO</span>
                                        <span class="h6 fw-bold text-success font-monospace mb-0">$0.00</span>
                                    </div>
                                </div>
                            @else
                                <div class="vstack gap-1">
                                    @foreach($monthCurrencyBreakdown as $cCode => $cItem)
                                        <div class="d-flex justify-content-between align-items-center px-2.5 py-1 bg-light rounded border border-light-subtle small font-monospace">
                                            <span class="badge bg-success text-white font-monospace">{{ $cCode }}</span>
                                            <div class="d-flex gap-3">
                                                <div>
                                                    <span class="text-secondary small me-1">Amount:</span>
                                                    <strong class="text-primary">{{ $cCode }} {{ number_format($cItem->total_amount, 2) }}</strong>
                                                </div>
                                                <div>
                                                    <span class="text-secondary small me-1">MCO:</span>
                                                    <strong class="text-success">{{ $cCode }} {{ number_format($cItem->total_mco, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: CURRENTLY LOGGED IN AGENTS (Logged in today) -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-person-lines-fill text-primary"></i> Currently Active / Logged-In Agents Today
            </h2>
            <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-3 py-1">
                <i class="bi bi-circle-fill me-1 small"></i> {{ $loggedInAgents->count() }} Active Today
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase border-bottom">
                        <tr>
                            <th class="px-3 py-3">Agent</th>
                            <th class="px-3 py-3">Email &amp; Extension</th>
                            <th class="px-3 py-3">Last Login Time</th>
                            <th class="px-3 py-3 text-center">Today's Bookings</th>
                            <th class="px-3 py-3 text-end">Today's MCO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loggedInAgents as $agent)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 14px;">
                                            {{ strtoupper(substr($agent->alias_name ?: $agent->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $agent->alias_name ?: $agent->name }}</div>
                                            <div class="small text-secondary">{{ $agent->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="fw-semibold text-dark">{{ $agent->email }}</div>
                                    <div class="small text-secondary">Ext: <span class="font-monospace fw-bold text-primary">{{ $agent->extension ?: 'N/A' }}</span> | Contact: {{ $agent->contact ?: 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace px-2.5 py-1.5">
                                        <i class="bi bi-clock me-1"></i> {{ $agent->last_login_at ? $agent->last_login_at->format('h:i A') . ' (' . $agent->last_login_at->diffForHumans() . ')' : 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1 rounded-pill">
                                        {{ number_format($agent->today_bookings_count ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    @if(isset($agent->today_currency_mco) && $agent->today_currency_mco->count() > 0)
                                        @foreach($agent->today_currency_mco as $cCode => $cMco)
                                            <div class="fw-bold text-success font-monospace small">
                                                {{ $cCode }} {{ number_format($cMco->total_mco, 2) }}
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="fw-bold text-success font-monospace fs-6">
                                            ${{ number_format($agent->today_total_mco ?? 0, 2) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-secondary fst-italic">
                                    <i class="bi bi-info-circle me-1"></i> No agents have logged in today yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 3: LAST 10 BOOKINGS -->
    <div class="card bg-white border-light-subtle shadow-sm">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Last 10 System Bookings
            </h2>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-link btn-sm text-primary text-decoration-none fw-semibold p-0">
                View All Bookings <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase border-bottom">
                        <tr>
                            <th class="px-3 py-3">PNR / Agent</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Customer Info</th>
                            <th class="px-3 py-3">Airline PNR</th>
                            <th class="px-3 py-3">Amount &amp; MCO</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestBookings as $booking)
                            <tr>
                                <td class="px-3 py-3">
                                    <span class="fw-bold text-primary font-monospace fs-6 d-block">#{{ $booking->airline_pnr }}</span>
                                    <span class="small text-secondary">Agent: <strong class="text-dark">{{ $booking->agent ? $booking->agent->alias_name : 'N/A' }}</strong></span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="small text-dark fw-semibold d-block">{{ $booking->created_at ? $booking->created_at->format('M d, Y') : '' }}</span>
                                    <span class="small text-secondary font-monospace">{{ $booking->created_at ? $booking->created_at->format('h:i A') : '' }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="fw-bold text-dark">{{ $booking->card_holder_name ?: 'N/A' }}</div>
                                    <div class="small text-secondary">{{ $booking->email_address }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace fw-bold fs-6">
                                        {{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 small">
                                    <div class="fw-bold text-dark">Amount: {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</div>
                                    <div class="fw-bold text-success font-monospace">MCO: {{ $booking->currency }} {{ number_format($booking->total_mco, 2) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    @php
                                        $bStatusBadge = match($booking->booking_status) {
                                            'booking_generated' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                            'email_auth_done' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'ticketed' => 'bg-secondary text-white',
                                            'booking_complete' => 'bg-success-subtle text-success border border-success-subtle',
                                            'void' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            default => 'bg-light text-dark border border-secondary-subtle'
                                        };
                                    @endphp
                                    <span class="badge {{ $bStatusBadge }} text-capitalize px-2 py-1 d-block mb-1">
                                        {{ str_replace('_', ' ', ucfirst($booking->booking_status)) }}
                                    </span>
                                    <span class="badge bg-light text-secondary border border-secondary-subtle text-uppercase px-2 py-0.5">
                                        {{ $booking->payment_status }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" onclick="showBookingDetailModal({{ $booking->id }})" class="btn btn-outline-primary btn-sm px-2 py-1" title="View Complete Booking Details">
                                             <i class="bi bi-eye me-1"></i> View
                                         </button>
                                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-outline-info btn-sm px-2 py-1" title="Edit Booking">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-secondary fst-italic">
                                    No recent bookings recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 4: AGENTS PERFORMANCE (Top 5 Agents by MCO + Pie Chart) -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-trophy-fill text-warning"></i> Agent Performance — Top 5 Agents by MCO ({{ now()->format('F Y') }})
            </h2>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-3 py-1">
                TOP PERFORMANCE LEADERBOARD
            </span>
        </div>
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <!-- Top 5 Leaderboard Table -->
                <div class="col-lg-7">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase border-bottom">
                                <tr>
                                    <th class="px-3 py-3" style="width: 60px;">Rank</th>
                                    <th class="px-3 py-3">Agent Name</th>
                                    <th class="px-3 py-3 text-center">Month Bookings</th>
                                    <th class="px-3 py-3 text-end">Total Amount</th>
                                    <th class="px-3 py-3 text-end">Total MCO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topAgents as $index => $tAgent)
                                    <tr>
                                        <td class="px-3 py-3 text-center">
                                            @if($index === 0)
                                                <span class="badge rounded-circle bg-warning text-dark p-2" title="1st Place"><i class="bi bi-trophy-fill"></i></span>
                                            @elseif($index === 1)
                                                <span class="badge rounded-circle bg-secondary text-white p-2" title="2nd Place"><i class="bi bi-award-fill"></i></span>
                                            @elseif($index === 2)
                                                <span class="badge rounded-circle bg-danger-subtle text-danger-emphasis p-2" title="3rd Place"><i class="bi bi-award"></i></span>
                                            @else
                                                <span class="badge rounded-circle bg-light text-dark border p-2 font-monospace">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="fw-bold text-dark">{{ $tAgent->alias_name ?: $tAgent->name }}</div>
                                            <div class="small text-secondary">{{ $tAgent->email }}</div>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="badge bg-primary-subtle text-primary font-monospace px-2.5 py-1">
                                                {{ number_format($tAgent->month_bookings_count ?? 0) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-end font-monospace text-secondary fw-semibold">
                                            @if(isset($tAgent->month_currency_mco) && $tAgent->month_currency_mco->count() > 0)
                                                @foreach($tAgent->month_currency_mco as $cCode => $cMco)
                                                    <div class="small">{{ $cCode }} {{ number_format($cMco->total_amount, 2) }}</div>
                                                @endforeach
                                            @else
                                                ${{ number_format($tAgent->month_total_amount ?? 0, 2) }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-end font-monospace fw-bold text-success">
                                            @if(isset($tAgent->month_currency_mco) && $tAgent->month_currency_mco->count() > 0)
                                                @foreach($tAgent->month_currency_mco as $cCode => $cMco)
                                                    <div class="small">{{ $cCode }} {{ number_format($cMco->total_mco, 2) }}</div>
                                                @endforeach
                                            @else
                                                ${{ number_format($tAgent->month_total_mco ?? 0, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-secondary fst-italic">
                                            No agent performance data available for this month.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MCO Distribution Pie / Doughnut Chart -->
                <div class="col-lg-5 text-center">
                    <div class="p-3 bg-light rounded-3 border border-light-subtle shadow-sm">
                        <h6 class="fw-bold text-dark mb-3 text-uppercase small">
                            <i class="bi bi-pie-chart-fill text-primary me-1"></i> MCO Distribution Share
                        </h6>
                        <div style="max-width: 320px; height: 260px; margin: 0 auto;">
                            <canvas id="topAgentsMcoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = @json($chartLabels);
        const data = @json($chartData);

        const ctx = document.getElementById('topAgentsMcoChart');
        if (ctx && labels.length > 0 && data.some(v => v > 0)) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#6366f1',
                            '#10b981',
                            '#f59e0b',
                            '#06b6d4',
                            '#ec4899'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    family: 'Outfit',
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw || 0;
                                    return ' ' + context.label + ': $' + Number(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
