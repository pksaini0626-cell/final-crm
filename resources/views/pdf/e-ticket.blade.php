<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Electronic Ticket Passenger Itinerary</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 20px;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 15px;
        }
        .merchant-name {
            font-size: 20px;
            font-weight: bold;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .airline-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
        }
        .meta-box {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: right;
        }
        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #0369a1;
            font-weight: bold;
            letter-spacing: 0.05em;
        }
        .meta-value {
            font-size: 14px;
            font-weight: bold;
            font-family: monospace;
            color: #0f172a;
        }
        .welcome-box {
            background-color: #f8fafc;
            border-left: 4px solid #0284c7;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0369a1;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .data-table {
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 10px;
            text-transform: uppercase;
            padding: 7px 10px;
            text-align: left;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }
        .data-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 11px;
        }
        .flight-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 14px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }
        .flight-header {
            background-color: #f8fafc;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .flight-body {
            padding: 12px;
        }
        .airport-code {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .layover-bar {
            background-color: #fffbeb;
            border: 1px solid #fef08a;
            color: #854d0e;
            padding: 6px 12px;
            border-radius: 4px;
            margin: 10px 0;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            page-break-inside: avoid;
        }
        .footer-box {
            margin-top: 30px;
            border-top: 1px solid #cbd5e1;
            padding-top: 12px;
            font-size: 10px;
            color: #475569;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        
        @php
            $fetchBase64Logo = function($url) {
                if (empty($url)) return null;
                if (!extension_loaded('gd')) return null;
                if (str_starts_with($url, 'data:image')) return $url;

                try {
                    $ctx = stream_context_create([
                        'http' => [
                            'timeout' => 3,
                            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ]
                    ]);
                    $imageData = @file_get_contents($url, false, $ctx);
                    if ($imageData && strlen($imageData) > 50) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_buffer($finfo, $imageData) ?: 'image/png';
                        finfo_close($finfo);
                        return 'data:' . $mime . ';base64,' . base64_encode($imageData);
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                return null;
            };

            $emailFlights = ($booking->flightSegments && $booking->flightSegments->isNotEmpty())
                ? $booking->flightSegments->sortBy('segment_number')->values()
                : $booking->bookingFlights->sortBy('departure_time')->values();

            $firstFlight = $emailFlights->first();
            $mainLogoUrl = null;
            if ($firstFlight) {
                $mainLogoUrl = $firstFlight->operated_by_logo ?: ($firstFlight->airline_logo ?: null);
                if (!$mainLogoUrl && !empty($firstFlight->operating_carrier) && strlen(trim($firstFlight->operating_carrier)) === 2) {
                    $mainLogoUrl = "https://pics.avs.io/200/50/" . strtoupper(trim($firstFlight->operating_carrier)) . ".png";
                }
            }
            if (!$mainLogoUrl && !empty($booking->airline_code) && strlen(trim($booking->airline_code)) === 2) {
                $mainLogoUrl = "https://pics.avs.io/200/50/" . strtoupper(trim($booking->airline_code)) . ".png";
            }

            $mainLogoUrl = $fetchBase64Logo($mainLogoUrl);

            $passengerName = $booking->card_holder_name;
            if (!$passengerName && $booking->passengers->isNotEmpty()) {
                $firstPax = $booking->passengers->first();
                $passengerName = trim(($firstPax->title ? $firstPax->title . ' ' : '') . $firstPax->first_name . ($firstPax->middle_name ? ' ' . $firstPax->middle_name : '') . ($firstPax->last_name ? ' ' . $firstPax->last_name : ''));
            }
            if (!$passengerName) {
                $passengerName = 'Valued Customer';
            }
        @endphp

        <!-- HEADER SECTION WITH AIRLINE LOGO & NAME -->
        <table class="header-table">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            @if($mainLogoUrl)
                                <td style="padding-right: 12px; vertical-align: middle;">
                                    <img src="{{ $mainLogoUrl }}" alt="" height="34" border="0" style="height: 34px; max-width: 140px;">
                                </td>
                            @endif
                            <td style="vertical-align: middle;">
                                <div class="airline-title">{{ $booking->airline_name ?: 'Airline Passenger Confirmation' }}</div>
                            
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <div class="meta-box">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="left">
                                    <span class="meta-label">Booking Ref:</span>
                                    <span style="font-weight: bold; color: #0f172a;">#{{ $booking->booking_id }}</span>
                                </td>
                                <td align="right">
                                    <span class="meta-label">Airline PNR:</span>
                                    <span class="meta-value">{{ $booking->airline_pnr ?: 'N/A' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td align="left" colspan="2" style="padding-top: 4px;">
                                    <span style="color: #64748b; font-size: 10px;">Date: {{ $booking->booking_date ? $booking->booking_date->format('d M Y') : date('d M Y') }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- PASSENGER ROSTER TABLE -->
        <div class="section-title">Passenger &amp; Ticket Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 45px;">S. No.</th>
                    <th>Passenger Name</th>
                    <th style="width: 80px;">Title</th>
                    <th style="width: 160px;">Ticket Number</th>
                    <th style="width: 80px;">Seat #</th>
                </tr>
            </thead>
            <tbody>
                @forelse($booking->passengers as $index => $passenger)
                    <tr>
                        <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                        <td style="font-weight: bold; color: #0f172a;">
                            {{ trim($passenger->first_name . ($passenger->middle_name ? ' ' . $passenger->middle_name : '') . ($passenger->last_name ? ' ' . $passenger->last_name : '')) }}
                        </td>
                        <td>{{ $passenger->title ?: 'ADT' }}</td>
                        <td style="font-family: monospace; font-weight: bold; color: #0284c7;">
                            {{ $passenger->ticket_number ?: 'TICKETED / ISSUED' }}
                        </td>
                        <td style="font-family: monospace; text-align: center;">
                            {{ $passenger->seat_number ?: 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; font-style: italic;">No passenger records attached.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- FLIGHT ITINERARY CARDS -->
        <div class="section-title">
            Flight Itinerary Schedule
            @if(!empty($booking->trip_type))
                <span style="font-size: 10px; color: #0369a1; font-weight: normal; margin-left: 8px;">
                    ({{ str_replace('_', ' ', strtoupper($booking->trip_type)) }})
                </span>
            @endif
        </div>

        @forelse($emailFlights as $index => $flight)
            @php
                $segLogoUrl = $flight->operated_by_logo ?: ($flight->airline_logo ?: null);
                if (!$segLogoUrl && !empty($flight->operated_by)) {
                    $cleanOp = strtoupper(trim($flight->operated_by));
                    if (strlen($cleanOp) === 2) {
                        $segLogoUrl = "https://pics.avs.io/200/50/{$cleanOp}.png";
                    } else {
                        $nameMap = [
                            'INDIGO' => '6E', 'AIR INDIA' => 'AI', 'SPICEJET' => 'SG', 'VISTARA' => 'UK',
                            'AKASA' => 'QP', 'AMERICAN' => 'AA', 'UNITED' => 'UA', 'DELTA' => 'DL',
                            'AIR FRANCE' => 'AF', 'BRITISH AIRWAYS' => 'BA', 'LUFTHANSA' => 'LH',
                            'EMIRATES' => 'EK', 'ETIHAD' => 'EY', 'QATAR' => 'QR', 'KLM' => 'KL',
                        ];
                        foreach ($nameMap as $k => $v) {
                            if (str_contains($cleanOp, $k)) {
                                $segLogoUrl = "https://pics.avs.io/200/50/{$v}.png";
                                break;
                            }
                        }
                    }
                }
                if (!$segLogoUrl && !empty($flight->operating_carrier) && strlen(trim($flight->operating_carrier)) === 2) {
                    $segLogoUrl = "https://pics.avs.io/200/50/" . strtoupper(trim($flight->operating_carrier)) . ".png";
                }
                $segLogoUrl = $fetchBase64Logo($segLogoUrl);
            @endphp

            <!-- Flight Segment Card -->
            <div class="flight-card">
                <div class="flight-header">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="left">
                                <strong style="color: #0f172a; font-size: 12px;">{{ $flight->departure_time ? $flight->departure_time->format('D, d M Y') : '' }}</strong>
                                <span style="background-color: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; margin-left: 6px;">{{ strtoupper($flight->status ?: 'Confirmed') }}</span>
                                <strong style="margin-left: 8px; color: #0369a1;">{{ $flight->airline_name ?: $flight->operating_carrier }} {{ $flight->operating_carrier }}{{ $flight->flight_number }}</strong>
                                <span style="color: #64748b; margin-left: 4px;">{{ $flight->cabin ?: 'Economy' }} ({{ $flight->booking_class ?: 'Y' }})</span>

                                @if(!empty($flight->operated_by))
                                    <span style="font-size: 10px; color: #0284c7; font-weight: bold; margin-left: 8px;">
                                        (Operated by {{ $flight->operated_by }})
                                    </span>
                                @endif
                            </td>
                            <td align="right" style="vertical-align: middle;">
                                @if($segLogoUrl)
                                    <img src="{{ $segLogoUrl }}" alt="Logo" height="20" border="0" style="height: 20px; max-width: 80px; vertical-align: middle;">
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="flight-body">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <!-- Departure Airport -->
                            <td width="40%" align="left" style="vertical-align: top;">
                                <div class="airport-code">{{ $flight->origin_airport }}</div>
                                <div style="font-size: 11px; font-weight: bold; color: #334155; margin-top: 2px;">{{ $flight->origin_city ?: $flight->origin_airport_name }}</div>
                                <div style="font-size: 10px; color: #64748b; margin-top: 4px;">
                                    Departure: <strong style="color: #0f172a;">{{ $flight->departure_time ? $flight->departure_time->format('H:i (h:i A)') : 'N/A' }}</strong>
                                </div>
                            </td>

                            <!-- Flight Indicator / Duration -->
                            <td width="20%" align="center" style="vertical-align: middle;">
                                <div style="font-size: 12px; font-weight: bold; color: #0284c7;">TO</div>
                                @if(!empty($flight->flight_duration))
                                    <div style="font-size: 9px; color: #64748b;">{{ $flight->flight_duration }}</div>
                                @endif
                            </td>

                            <!-- Arrival Airport -->
                            <td width="40%" align="right" style="vertical-align: top;">
                                <div class="airport-code">{{ $flight->destination_airport }}</div>
                                <div style="font-size: 11px; font-weight: bold; color: #334155; margin-top: 2px;">{{ $flight->destination_city ?: $flight->destination_airport_name }}</div>
                                <div style="font-size: 10px; color: #64748b; margin-top: 4px;">
                                    Arrival: <strong style="color: #0f172a;">{{ $flight->arrival_time ? $flight->arrival_time->format('H:i (h:i A)') : 'N/A' }}</strong>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Connection Layover Bar -->
            @if(isset($emailFlights[$index + 1]))
                @php
                    $arrival = $flight->arrival_time;
                    $nextDeparture = $emailFlights[$index + 1]->departure_time;
                @endphp
                @if($arrival && $nextDeparture)
                    @php
                        $diff = $arrival->diff($nextDeparture);
                        $hours = ($diff->days * 24) + $diff->h;
                        $minutes = $diff->i;
                    @endphp
                    <div class="layover-bar">
                        Connection Layover in {{ $flight->destination_airport }} ({{ $hours }}h {{ $minutes }}m)
                    </div>
                @endif
            @endif
        @empty
            <p style="font-style: italic; color: #64748b; text-align: center;">No flight details attached to this booking.</p>
        @endforelse

        <!-- FOOTER & LEGAL NOTES -->
        <div class="footer-box">
            <div style="font-weight: bold; color: #0f172a; margin-bottom: 4px;">
                Important Airport Check-In &amp; Travel Guidelines:
            </div>
            <div>
                • <strong>Check-In Recommendation:</strong> Please arrive at the airport at least <strong>2 hours prior to departure for domestic flights</strong> and <strong>3 hours prior to departure for international flights</strong>.
            </div>
            <div>
                • <strong>Travel Documents:</strong> All passengers must present valid government-issued photo identification (or passport and visas for international travel) at check-in.
            </div>
            <div>
                • <strong>24/7 Support Desk:</strong> For any flight changes, cancellations, or refund queries, please call us at <strong>+1-888-476-0932</strong>.
            </div>
            <div style="margin-top: 8px; color: #94a3b8; font-size: 9px;">
                Carriage and other services provided by the carrier are subject to conditions of carriage, which are hereby incorporated by reference.
            </div>
        </div>

    </div>
</body>
</html>
