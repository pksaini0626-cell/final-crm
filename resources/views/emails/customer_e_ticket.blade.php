<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your E-Ticket Travel Itinerary</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #222222; margin: 0; padding: 20px 10px; line-height: 1.5; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    
    <!-- MAIN WRAPPER TABLE -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9; width: 100%; margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 10px 0;">
                
                <!-- CONTAINER TABLE (680px max) -->
                <table width="680" border="0" cellpadding="0" cellspacing="0" style="max-width: 680px; width: 100%; background-color: #ffffff; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #dcdfe6; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    
                    <!-- HEADER SECTION -->
                    <tr>
                        <td align="center" style="background-color: #065f46; color: #ffffff; padding: 24px 30px; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; font-family: Arial, sans-serif;">Electronic Ticket &amp; Travel Itinerary</h1>
                            <div style="margin-top: 8px; font-size: 13px; color: #d1fae5; font-family: Arial, sans-serif;">
                                Booking Ref: <strong style="color: #ffffff;">#{{ $booking->booking_id }}</strong> &nbsp;|&nbsp; 
                                Airline PNR: <strong style="color: #ffffff;">{{ $booking->airline_pnr ?: 'N/A' }}</strong>
                            </div>
                        </td>
                    </tr>

                    <!-- CONTENT BODY -->
                    <tr>
                        <td style="padding: 25px 30px; background-color: #ffffff;">
                            
                            <!-- SALUTATION & GREETING -->
                            @php
                                $passengerName = $booking->card_holder_name;
                                if (!$passengerName && $booking->passengers->isNotEmpty()) {
                                    $firstPax = $booking->passengers->first();
                                    $passengerName = trim(($firstPax->title ? $firstPax->title . ' ' : '') . $firstPax->first_name . ($firstPax->middle_name ? ' ' . $firstPax->middle_name : '') . ($firstPax->last_name ? ' ' . $firstPax->last_name : ''));
                                }
                                if (!$passengerName) {
                                    $passengerName = 'Valued Customer';
                                }
                            @endphp

                            <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 12px; font-family: Arial, sans-serif;">
                                Dear {{ $passengerName }},
                            </div>

                            <div style="font-size: 14px; color: #334155; margin-bottom: 14px; font-family: Arial, sans-serif;">
                                We are pleased to confirm your reservation. Your booking has been successfully ticketed, and your e-tickets are attached to this email for your reference.
                            </div>

                            <div style="font-size: 14px; color: #334155; margin-bottom: 14px; font-family: Arial, sans-serif;">
                                Please review the attached itinerary carefully and verify that all passenger names, travel dates, flight timings, and other details are correct. If you notice any discrepancies, kindly contact us immediately.
                            </div>

                            <div style="font-size: 14px; color: #334155; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                Please arrive at the airport at least 2 hours prior to departure for domestic flights and 3 hours prior to departure for international flights.
                            </div>

                            <!-- 24/7 SUPPORT & NEED ASSISTANCE OR CHANGES BOX -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f0fdf4; border-left: 4px solid #059669; border-top: 1px solid #d1fae5; border-right: 1px solid #d1fae5; border-bottom: 1px solid #d1fae5; border-radius: 4px; margin: 18px 0;">
                                <tr>
                                    <td style="padding: 14px 18px; font-size: 13px; color: #166534; line-height: 1.6; font-family: Arial, sans-serif;">
                                        <p style="margin: 0 0 6px 0; font-weight: bold; font-size: 14px;">
                                            📞 Need Assistance or Changes?
                                        </p>
                                        <p style="margin: 0;">
                                            For any changes, cancellations, or refund queries, please call us at <strong style="color: #047857; font-size: 14px;" x-text="supportPhone || '{{ $supportPhone ?? '+1-888-476-0932' }}'">{{ $supportPhone ?? '+1-888-476-0932' }}</strong> (available 24/7). Please note that date/routing changes and cancellations are subject to airline fare rules, penalties, and processing fees.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- PLEASE NOTE / SPECIAL INSTRUCTIONS CALLOUT -->
                            <div x-show="customNote &amp;&amp; customNote.trim().length > 0">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #fffbe6; border-left: 4px solid #d97706; border-top: 1px solid #fef08a; border-right: 1px solid #fef08a; border-bottom: 1px solid #fef08a; border-radius: 4px; margin: 16px 0;">
                                    <tr>
                                        <td style="padding: 14px 18px; font-size: 13px; color: #78350f; font-family: Arial, sans-serif; line-height: 1.6;">
                                            <strong style="color: #92400e; font-size: 14px; display: block; margin-bottom: 4px;">📌 Please Note / Special Instructions:</strong>
                                            <span x-text="customNote" style="white-space: pre-line;">{{ $customNote ?? '' }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            @if(!empty($customNote))
                                <template x-if="typeof customNote === 'undefined'">
                                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #fffbe6; border-left: 4px solid #d97706; border-top: 1px solid #fef08a; border-right: 1px solid #fef08a; border-bottom: 1px solid #fef08a; border-radius: 4px; margin: 16px 0;">
                                        <tr>
                                            <td style="padding: 14px 18px; font-size: 13px; color: #78350f; font-family: Arial, sans-serif; line-height: 1.6;">
                                                <strong style="color: #92400e; font-size: 14px; display: block; margin-bottom: 4px;">📌 Please Note / Special Instructions:</strong>
                                                <span style="white-space: pre-line;">{{ $customNote }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </template>
                            @endif

                            <!-- PASSENGER DETAILS TABLE -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Passenger &amp; Ticket Roster
                            </div>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: #f1f5f9;">
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1; width: 45px;">No.</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Passenger Name</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1; width: 70px;">Gender</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Ticket Number</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1; width: 90px;">Seat #</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($booking->passengers as $idx => $pax)
                                        <tr>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b; font-weight: bold;">{{ $idx + 1 }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #0f172a; font-weight: bold;">
                                                {{ trim(($pax->title ? $pax->title . ' ' : '') . $pax->first_name . ($pax->middle_name ? ' ' . $pax->middle_name : '') . ($pax->last_name ? ' ' . $pax->last_name : '')) }}
                                            </td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">
                                                {{ $pax->gender ?: 'N/A' }}
                                            </td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #0284c7; font-family: monospace; font-weight: bold;">
                                                {{ $pax->ticket_number ?: 'TICKETED / ISSUED' }}
                                            </td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b; font-family: monospace;">
                                                {{ $pax->seat_number ?: 'N/A' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="padding: 12px; text-align: center; color: #64748b; border: 1px solid #e2e8f0;">No passenger records attached.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- FLIGHT ITINERARY CARDS -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Flight Itinerary &amp; Schedule
                                @if(!empty($booking->trip_type))
                                    <span style="font-size: 11px; background-color: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; margin-left: 8px; text-transform: uppercase;">
                                        {{ str_replace('_', ' ', $booking->trip_type) }}
                                    </span>
                                @endif
                            </div>

                            @php
                                $emailFlights = ($booking->flightSegments && $booking->flightSegments->isNotEmpty())
                                    ? $booking->flightSegments
                                    : $booking->bookingFlights;
                            @endphp

                            @forelse($emailFlights as $flight)
                                @php
                                    $logoUrl = $flight->operated_by_logo ?: ($flight->airline_logo ?: null);
                                    if (!$logoUrl && !empty($flight->operated_by)) {
                                        $cleanOp = strtoupper(trim($flight->operated_by));
                                        if (strlen($cleanOp) === 2) {
                                            $logoUrl = "https://pics.avs.io/200/50/{$cleanOp}.png";
                                        } else {
                                            $nameMap = [
                                                'INDIGO' => '6E', 'AIR INDIA' => 'AI', 'SPICEJET' => 'SG', 'VISTARA' => 'UK',
                                                'AKASA' => 'QP', 'AMERICAN' => 'AA', 'UNITED' => 'UA', 'DELTA' => 'DL',
                                                'AIR FRANCE' => 'AF', 'BRITISH AIRWAYS' => 'BA', 'LUFTHANSA' => 'LH',
                                                'EMIRATES' => 'EK', 'ETIHAD' => 'EY', 'QATAR' => 'QR', 'KLM' => 'KL',
                                            ];
                                            foreach ($nameMap as $k => $v) {
                                                if (str_contains($cleanOp, $k)) {
                                                    $logoUrl = "https://pics.avs.io/200/50/{$v}.png";
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if (!$logoUrl && !empty($flight->operating_carrier) && strlen(trim($flight->operating_carrier)) === 2) {
                                        $logoUrl = "https://pics.avs.io/200/50/" . strtoupper(trim($flight->operating_carrier)) . ".png";
                                    }
                                @endphp
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 14px; border-collapse: separate; overflow: hidden; font-family: Arial, sans-serif;">
                                    <!-- Flight Header Row -->
                                    <tr>
                                        <td style="background-color: #f8fafc; padding: 10px 14px; font-size: 12px; border-bottom: 1px solid #e2e8f0;">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td align="left" style="font-size: 12px; color: #334155;">
                                                        <strong style="color: #0f172a;">{{ $flight->departure_time ? $flight->departure_time->format('D, M d, Y') : '' }}</strong>
                                                        <span style="background-color: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 10px; font-weight: bold; font-size: 10px; margin-left: 6px; display: inline-block;">{{ $flight->status ?: 'Confirmed' }}</span>
                                                        <strong style="margin-left: 6px; color: #0f172a;">{{ $flight->airline_name ?: $flight->operating_carrier }} {{ $flight->operating_carrier }}{{ $flight->flight_number }}</strong>
                                                        <span style="color: #64748b; margin-left: 4px;">{{ $flight->cabin ?: 'Economy' }} ({{ $flight->booking_class ?: 'Y' }})</span>

                                                        @if(!empty($flight->operated_by))
                                                            <div style="font-size: 11px; color: #0284c7; font-weight: bold; margin-top: 4px;">
                                                                Operated by {{ $flight->operated_by }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td align="right" style="font-size: 12px; vertical-align: middle;">
                                                        @if($logoUrl)
                                                            <img src="{{ $logoUrl }}" alt="{{ $flight->operated_by ?: $flight->airline_name }}" height="22" border="0" style="height: 22px; max-width: 90px; vertical-align: middle; display: inline-block;">
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <!-- Flight Body Row -->
                                    <tr>
                                        <td style="padding: 14px; font-size: 13px; background-color: #ffffff;">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="42%" align="left" style="vertical-align: top;">
                                                        <div style="font-size: 20px; font-weight: 900; color: #0f172a; font-family: Arial, sans-serif;">{{ $flight->origin_airport }}</div>
                                                        <div style="font-size: 13px; font-weight: bold; color: #334155; margin-top: 2px;">{{ $flight->origin_city ?: $flight->origin_airport_name }}</div>
                                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                                            Departure: <strong style="color: #0f172a;">{{ $flight->departure_time ? $flight->departure_time->format('H:i (h:i A)') : 'N/A' }}</strong>
                                                        </div>
                                                    </td>

                                                    <td width="16%" align="center" style="vertical-align: middle;">
                                                        @if(!empty($flight->flight_duration))
                                                            <div style="font-size: 14px; font-weight: bold; color: #64748b; margin-top: 2px;">{{ $flight->flight_duration }}</div>
                                                        @endif
                                                    </td>

                                                    <td width="42%" align="right" style="vertical-align: top;">
                                                        <div style="font-size: 20px; font-weight: 900; color: #0f172a; font-family: Arial, sans-serif;">{{ $flight->destination_airport }}</div>
                                                        <div style="font-size: 13px; font-weight: bold; color: #334155; margin-top: 2px;">{{ $flight->destination_city ?: $flight->destination_airport_name }}</div>
                                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                                            Arrival: <strong style="color: #0f172a;">{{ $flight->arrival_time ? $flight->arrival_time->format('H:i (h:i A)') : 'N/A' }}</strong>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <!-- Transit / Layover Banner Row -->
                                    @if(!empty($flight->transit_text))
                                        <tr>
                                            <td align="center" style="background-color: #f1f5f9; padding: 6px 10px; font-size: 11px; font-weight: bold; color: #334155; border-top: 1px solid #e2e8f0; text-align: center;">
                                                &#x1F550; {{ $flight->transit_text }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            @empty
                                <div style="padding: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; color: #64748b; font-size: 13px;">
                                    Flight segment details attached in PDF itinerary.
                                </div>
                            @endforelse

                            <!-- SIGN-OFF -->
                            <div style="margin-top: 28px; font-size: 14px; color: #334155; font-family: Arial, sans-serif;">
                                Thank you for choosing us for your travel plans. We wish you a safe and pleasant journey!
                            </div>

                            <div style="margin-top: 20px; font-size: 14px; color: #0f172a; font-family: Arial, sans-serif;">
                                Warm regards,<br>
                                <strong>Reservation Desk</strong><br>
                                <span style="color: #059669; font-weight: bold;">📞 {{ $supportPhone ?? '+1-888-476-0932' }}</span>
                            </div>

                        </td>
                    </tr>

                    <!-- FOOTER SECTION -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 18px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
                            This is an automated e-ticket confirmation email.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
