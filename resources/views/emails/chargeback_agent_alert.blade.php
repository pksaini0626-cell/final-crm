<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chargeback &amp; Dispute Alert</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; margin: 0; padding: 20px;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 650px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        <!-- HEADER -->
        <tr>
            <td style="background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%); padding: 24px 28px; text-align: left;">
                <div style="display: inline-block; background-color: rgba(255,255,255,0.2); color: #ffffff; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; padding: 4px 10px; border-radius: 4px; margin-bottom: 8px;">
                    Chargeback Control Desk
                </div>
                <h1 style="color: #ffffff; font-size: 21px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    ⚠️ Dispute / Chargeback Status Updated
                </h1>
                <p style="color: #fee2e2; font-size: 13px; margin: 0; font-family: monospace;">
                    Booking Reference: #{{ $booking->booking_id }} &bull; PNR: {{ $booking->airline_pnr ?: ($booking->gk_pnr ?: ($chargeback?->pnr ?: 'N/A')) }}
                </p>
            </td>
        </tr>

        <!-- CONTENT -->
        <tr>
            <td style="padding: 28px;">
                <p style="font-size: 15px; margin-top: 0; color: #334155;">
                    Hello <strong>{{ $booking->agent ? ($booking->agent->alias_name ?: $booking->agent->name) : 'Agent' }}</strong>,
                </p>
                <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                    This is an automated notification from the Chargeback team. A dispute or status modification has occurred on your booking confirmation <strong>#{{ $booking->booking_id }}</strong>. Please review the updated details below.
                </p>

                <!-- STATUS CHANGE DIFF BOX -->
                <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 18px; margin-bottom: 24px;">
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #9f1239; margin-bottom: 12px;">
                        📌 Status Update Summary
                    </div>
                    <table width="100%" border="0" cellpadding="6" cellspacing="0" style="font-size: 13px;">
                        @if($oldDisputeType !== null || $newDisputeType !== null)
                        <tr>
                            <td style="width: 40%; font-weight: bold; color: #881337;">Dispute Type:</td>
                            <td>
                                <span style="font-family: monospace; background-color: #ffe4e6; color: #9f1239; padding: 2px 8px; border-radius: 4px; text-decoration: line-through;">
                                    {{ $oldDisputeType ? strtoupper($oldDisputeType) : 'NONE' }}
                                </span>
                                <span style="margin: 0 6px; color: #9f1239; font-weight: bold;">&rarr;</span>
                                <span style="font-family: monospace; background-color: #dc2626; color: #ffffff; padding: 3px 10px; border-radius: 4px; font-weight: bold;">
                                    {{ $newDisputeType ? strtoupper($newDisputeType) : 'NONE' }}
                                </span>
                            </td>
                        </tr>
                        @endif

                        @if($oldStatus !== null || $newStatus !== null)
                        <tr>
                            <td style="font-weight: bold; color: #881337;">Booking / Current Status:</td>
                            <td>
                                <span style="font-family: monospace; background-color: #ffe4e6; color: #9f1239; padding: 2px 8px; border-radius: 4px; text-decoration: line-through;">
                                    {{ $oldStatus ? strtoupper(str_replace('_', ' ', $oldStatus)) : 'NONE' }}
                                </span>
                                <span style="margin: 0 6px; color: #9f1239; font-weight: bold;">&rarr;</span>
                                <span style="font-family: monospace; background-color: #991b1b; color: #ffffff; padding: 3px 10px; border-radius: 4px; font-weight: bold;">
                                    {{ $newStatus ? strtoupper(str_replace('_', ' ', $newStatus)) : 'NONE' }}
                                </span>
                            </td>
                        </tr>
                        @endif

                        <tr>
                            <td style="font-weight: bold; color: #881337;">Updated By:</td>
                            <td style="color: #1e293b; font-weight: 600;">
                                {{ $changedBy ? ($changedBy->alias_name ?: $changedBy->name) : 'Chargeback Desk' }}
                                <span style="color: #64748b; font-weight: normal; font-size: 12px; margin-left: 6px;">({{ $disputeTime }})</span>
                            </td>
                        </tr>

                        @if(!empty($note))
                        <tr>
                            <td style="font-weight: bold; color: #881337; vertical-align: top;">Note / Reason:</td>
                            <td style="color: #0f172a; font-style: italic; background-color: #ffffff; padding: 6px 10px; border-radius: 4px; border: 1px solid #fecdd3;">
                                {{ $note }}
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- 1. BOOKING & FINANCIAL INFORMATION -->
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 24px;">
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                        🛫 Booking &amp; Financial Details
                    </div>
                    <table width="100%" border="0" cellpadding="5" cellspacing="0" style="font-size: 13px;">
                        <tr>
                            <td style="width: 38%; font-weight: bold; color: #64748b;">Booking Reference:</td>
                            <td style="font-family: monospace; font-weight: bold; color: #0284c7;">#{{ $booking->booking_id }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Airline PNR:</td>
                            <td style="font-family: monospace; font-weight: bold; color: #d97706;">{{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}</td>
                        </tr>
                        @if($booking->airline_name)
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Airline:</td>
                            <td>{{ $booking->airline_name }} ({{ $booking->airline_code ?: 'N/A' }})</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Booking Date:</td>
                            <td>{{ $booking->booking_date ? $booking->booking_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Customer / Cardholder:</td>
                            <td style="font-weight: bold; color: #1e293b;">{{ $booking->card_holder_name ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Customer Email:</td>
                            <td><a href="mailto:{{ $booking->email_address }}" style="color: #0284c7; text-decoration: none;">{{ $booking->email_address }}</a></td>
                        </tr>
                        @if($booking->calling_number || $booking->billing_phone)
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Customer Phone:</td>
                            <td>{{ $booking->calling_number ?: $booking->billing_phone }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Total Booking Amount:</td>
                            <td style="font-family: monospace; font-weight: bold; color: #059669;">
                                {{ $booking->currency ?: 'USD' }} {{ number_format($booking->total_amount, 2) }}
                            </td>
                        </tr>
                        @if($chargeback && $chargeback->disputed_amount > 0)
                        <tr>
                            <td style="font-weight: bold; color: #dc2626;">Disputed Amount:</td>
                            <td style="font-family: monospace; font-weight: bold; color: #dc2626; font-size: 14px;">
                                {{ $chargeback->currency ?: ($booking->currency ?: 'USD') }} {{ number_format($chargeback->disputed_amount, 2) }}
                            </td>
                        </tr>
                        @endif
                        @if($chargeback)
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Dispute Portal:</td>
                            <td><span style="background-color: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-weight: 600;">{{ $chargeback->portal }}</span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Case Number:</td>
                            <td style="font-family: monospace; font-weight: bold;">{{ $chargeback->case_number }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- 2. PASSENGER DETAILS TABLE -->
                @if($booking->passengers && $booking->passengers->count() > 0)
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 8px;">
                        👥 Passengers on Booking ({{ $booking->passengers->count() }})
                    </div>
                    <table width="100%" border="0" cellpadding="8" cellspacing="0" style="font-size: 12px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 11px;">
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">#</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Full Name</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Gender</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">DOB</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Ticket #</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Seat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->passengers as $idx => $pax)
                            <tr style="border-bottom: 1px solid #f1f5f9; background-color: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                                <td style="padding: 8px 10px; font-weight: bold; color: #64748b;">{{ $idx + 1 }}</td>
                                <td style="padding: 8px 10px; font-weight: bold; color: #1e293b;">
                                    {{ $pax->title ? $pax->title . ' ' : '' }}{{ $pax->first_name }} {{ $pax->middle_name ? $pax->middle_name . ' ' : '' }}{{ $pax->last_name }}
                                </td>
                                <td style="padding: 8px 10px; color: #64748b;">{{ $pax->gender ?: 'N/A' }}</td>
                                <td style="padding: 8px 10px; font-family: monospace; color: #64748b;">{{ $pax->dob ? $pax->dob->format('Y-m-d') : 'N/A' }}</td>
                                <td style="padding: 8px 10px; font-family: monospace; font-weight: bold; color: #0284c7;">{{ $pax->ticket_number ?: 'N/A' }}</td>
                                <td style="padding: 8px 10px; font-family: monospace; font-weight: bold; color: #059669;">{{ $pax->seat_number ?: 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- 3. FLIGHT ITINERARY (IF AVAILABLE) -->
                @php
                    $flightSegments = $booking->flightSegments && $booking->flightSegments->count() > 0
                        ? $booking->flightSegments
                        : ($booking->bookingFlights ?: collect());
                @endphp
                @if($flightSegments->count() > 0)
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 8px;">
                        ✈️ Flight Itinerary
                    </div>
                    <table width="100%" border="0" cellpadding="8" cellspacing="0" style="font-size: 12px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 11px;">
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Carrier / Flight</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Route</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Departure</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Arrival</th>
                                <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #cbd5e1;">Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($flightSegments as $idx => $fl)
                            <tr style="border-bottom: 1px solid #f1f5f9; background-color: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                                <td style="padding: 8px 10px; font-family: monospace; font-weight: bold; color: #0284c7;">
                                    {{ $fl->operating_carrier ?: ($booking->airline_code ?: '') }} {{ $fl->flight_number }}
                                </td>
                                <td style="padding: 8px 10px; font-family: monospace; font-weight: bold; color: #1e293b;">
                                    {{ $fl->origin_airport ?: ($fl->origin ?: 'N/A') }} &rarr; {{ $fl->destination_airport ?: ($fl->destination ?: 'N/A') }}
                                </td>
                                <td style="padding: 8px 10px; color: #475569;">
                                    {{ $fl->departure_time ? (is_string($fl->departure_time) ? date('M d, H:i', strtotime($fl->departure_time)) : $fl->departure_time->format('M d, H:i')) : 'N/A' }}
                                </td>
                                <td style="padding: 8px 10px; color: #475569;">
                                    {{ $fl->arrival_time ? (is_string($fl->arrival_time) ? date('M d, H:i', strtotime($fl->arrival_time)) : $fl->arrival_time->format('M d, H:i')) : 'N/A' }}
                                </td>
                                <td style="padding: 8px 10px; font-family: monospace; color: #64748b;">
                                    {{ $fl->booking_class ?: 'Y' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 15px; margin-top: 20px; border-radius: 6px; text-align: center;">
                    <p style="font-size: 12px; color: #64748b; margin: 0;">
                        Please check your CRM dashboard for full case remarks, customer documentation, and transaction logs.
                    </p>
                </div>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td style="background-color: #0f172a; padding: 16px 28px; text-align: center; color: #94a3b8; font-size: 11px;">
                <p style="margin: 0;">
                    Callinggenie CRM &bull; Chargeback &amp; Risk Mitigation Desk
                </p>
                <p style="margin: 4px 0 0 0; color: #64748b;">
                    This is an automated system dispatch. Responses to this address are not monitored.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
