<?php
    $authUrl = $authUrl ?? route('customer.authorize', ['booking' => $booking->id, 'hash' => \App\Http\Controllers\CustomerAuthController::generateHash($booking)]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $customSubject ?? 'Payment Authorization & Booking Confirmation' }}</title>
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
                        <td align="center" style="background-color: #1e293b; color: #ffffff; padding: 24px 30px; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; font-family: Arial, sans-serif;">Payment Authorization &amp; Booking Confirmation</h1>
                            <div style="margin-top: 8px; font-size: 13px; color: #94a3b8; font-family: Arial, sans-serif;">
                                Confirmation code : <strong style="color: #ffffff;">{{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}</strong>
                            </div>
                        </td>
                    </tr>

                    <!-- CONTENT BODY -->
                    <tr>
                        <td style="padding: 25px 30px; background-color: #ffffff;">
                            
                            <!-- SALUTATION & GREETING -->
                            <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 6px; font-family: Arial, sans-serif;">
                                Dear {{ $booking->card_holder_name ?: 'Customer' }},
                            </div>
                            <div style="font-size: 14px; color: #334155; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                Greetings of the day !!
                            </div>

                            <!-- SERVICE STATEMENT -->
                            @php
                                $airlineName = $booking->airline_name ?: ($booking->airline_code ?: 'American Airlines');
                                $pnr = $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A');
                                $merchantName = $booking->merchant ?: 'Travelomile';
                                $serviceProvidedText = match($booking->service_provided) {
                                    'new_booking' => 'booked your reservation',
                                    'exchange' => 'processed the exchange for your reseervation',
                                    'cancellation' => 'processed the cancellation',
                                    'refund' => 'processed the refund request',
                                    'seat_selection' => 'assigned the seats',
                                    'baggage_addition' => 'added the baggage',
                                    'others' => 'processed your service request',
                                    'cancel_and_refund' => 'processed the cancellation and refund',
                                    'name_correction' => 'processed the name correction',
                                    'flight_upgrade' => 'processed the flight upgrade',
                                    'dob_correction' => 'processed the date of birth correction',
                                    'pet_in_cabin' => 'added the pet in cabin request',
                                    'ancillary_refund' => 'processed the ancillary refund',
                                    'general_inquiry' => 'assisted you with your inquiry',
                                    'infant_ticket' => 'confirmed the infant ticket',
                                    default => 'reserved your flights'
                                };
                            @endphp

                            <div style="font-size: 14px; color: #334155; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                As per our conversation and as agreed, We have {{ $serviceProvidedText }} for your reservation with {{ $airlineName }} under Confirmation <strong style="color: #0f172a;">{{ $pnr }}</strong>. Please see the details below.
                            </div>

                            <div style="font-weight: bold; font-size: 14px; margin-bottom: 16px; color: #0f172a; font-family: Arial, sans-serif;">
                                Total cost for all passengers: {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }} (all incl. taxes and fees).
                            </div>

                            <!-- LEGAL AUTHORIZATION DECLARATION BOX -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-left: 4px solid #4f46e5; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; border-radius: 4px; margin: 18px 0;">
                                <tr>
                                    <td style="padding: 14px 18px; font-size: 13px; color: #334155; line-height: 1.6; font-family: Arial, sans-serif;">
                                        <p style="margin: 0 0 10px 0;">
                                            As per our telephonic conversation I, <strong style="color: #0f172a;">{{ $booking->card_holder_name ?: 'Customer' }}</strong>, authorize {{ $airlineName }} / {{ $merchantName }} to process the above-mentioned charges under their respective merchants for charging my <strong style="color: #0f172a;">{{ $booking->card_type ?? 'Card'}}</strong>&nbsp;<strong style="color: #0f172a;">******{{ $booking->card_last_4 ?: 'XXXX' }}</strong> card for the booking the below-mentioned itinerary with {{ $airlineName }}.
                                        </p>
                                        <p style="margin: 0 0 10px 0;">
                                            This payment authorization is for the amount indicated above and is valid for one-time use only. I certify that I am <strong style="color: #0f172a;">{{ $booking->card_holder_name ?: 'Customer' }}</strong>, an authorized user of this card and that I will not dispute the payment with my credit/debit card company/bank.
                                        </p>
                                        <p style="margin: 0; font-weight: bold; color: #4f46e5;">
                                            Kindly confirm your acceptance of the terms and agreement to the declaration by replying to this email with 'I Agree' or 'I Authorize'.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CHARGES DESCRIPTION -->
                            @php
                                $paidToAirline = floatval($booking->paid_to_airline ?? 0);
                                $agencyFee = max(0, floatval($booking->total_amount ?? 0) - $paidToAirline);
                            @endphp

                            @if($paidToAirline > 0)
                                <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                    Charges Description:
                                </div>
                                <div style="font-size: 13px; margin-bottom: 6px; color: #1e293b; font-family: Arial, sans-serif;">
                                    Charge 1: <strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($paidToAirline, 2) }}</strong> ({{ $airlineName }}, incl. base fare)
                                </div>
                                @if($agencyFee > 0)
                                    <div style="font-size: 13px; margin-bottom: 16px; color: #1e293b; font-family: Arial, sans-serif;">
                                        Charge 2: <strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($agencyFee, 2) }}</strong> ({{ $merchantName }}, incl. taxes &amp; fees)
                                    </div>
                                @endif
                            @else
                                <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                    Charges Description
                                </div>
                                <div style="font-size: 13px; margin-bottom: 16px; color: #1e293b; font-family: Arial, sans-serif;">
                                    1. <strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong> ({{ $merchantName }}, incl. the taxes and fees)
                                </div>
                            @endif

                            <!-- PASSENGER DETAILS TABLE -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Passenger Details
                            </div>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: #f1f5f9;">
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">S. No.</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Type</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">First Name</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Middle Name</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Last Name</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Gender</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">DOB</th>
                                        <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Price</th>
                                         <th style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1;">Seat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($booking->passengers as $idx => $pax)
                                        <tr>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $idx + 1 }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->pax_index ? 'ADT' : 'ADT' }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->first_name }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->middle_name ?: '-' }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->last_name }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->gender ? ($pax->gender == 'F' ? 'Female' : ($pax->gender == 'O' ? 'Other' : 'Male')) : ($pax->title == 'MS' || $pax->title == 'MRS' ? 'Female' : 'Male') }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->dob ? ($pax->dob instanceof \DateTimeInterface ? $pax->dob->format('d M Y') : \Carbon\Carbon::parse($pax->dob)->format('d M Y')) : '-' }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->currency }} {{ number_format($booking->total_amount / max(count($booking->passengers), 1), 2) }}</td>
                                            <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $pax->seat_number ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" style="padding: 12px; text-align: center; color: #64748b; border: 1px solid #e2e8f0;">No passenger records attached.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>


                            <!-- FLIGHT ITINERARY CARDS -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Flight Itinerary
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
                                                        <strong style="color: #0f172a;">{{ $flight->departure_time ? $flight->departure_time->format('D, M d') : '' }}</strong>
                                                        <span style="background-color: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 10px; font-weight: bold; font-size: 10px; margin-left: 6px; display: inline-block;">{{ $flight->status ?: 'Confirmed' }}</span>
                                                        <strong style="margin-left: 6px; color: #0f172a;">{{ $flight->airline_name ?: $flight->operating_carrier }} {{ $flight->operating_carrier }}{{ $flight->flight_number }}</strong>
                                                        <span style="color: #64748b; margin-left: 4px;">{{ $flight->cabin ?: 'Economy' }} ({{ $flight->booking_class ?: 'Y' }})</span>

                                                        @if(!empty($flight->operated_by))
                                                            <div style="font-size: 11px; color: #0284c7; font-weight: bold; margin-top: 4px;">
                                                                &#x2708;&#xFE0F; Operated by {{ $flight->operated_by }}
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
                                                        <div style="font-weight: bold; margin-top: 2px; color: #1e293b; font-family: Arial, sans-serif;">{{ $flight->departure_time ? $flight->departure_time->format('h:i A') : '' }}</div>
                                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; font-family: Arial, sans-serif;">{{ $flight->origin_airport_name ?: ($flight->origin_city ?: $flight->origin_airport) }}</div>
                                                    </td>
                                                    <td width="16%" align="center" style="vertical-align: middle; color: #94a3b8; font-size: 18px; font-weight: bold;">
                                                        &#10140;
                                                    </td>
                                                    <td width="42%" align="right" style="vertical-align: top;">
                                                        <div style="font-size: 20px; font-weight: 900; color: #0f172a; font-family: Arial, sans-serif;">{{ $flight->destination_airport }}</div>
                                                        <div style="font-weight: bold; margin-top: 2px; color: #1e293b; font-family: Arial, sans-serif;">
                                                            {{ $flight->arrival_time ? $flight->arrival_time->format('h:i A') : '' }}
                                                            @if($flight->day_offset > 0)
                                                                <span style="color: #d97706; font-size: 10px;">(On +{{ $flight->day_offset }} Day)</span>
                                                            @endif
                                                        </div>
                                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; font-family: Arial, sans-serif;">{{ $flight->destination_airport_name ?: ($flight->destination_city ?: $flight->destination_airport) }}</div>
                                                    </td>
                                                </tr>
                                            </table>

                                            @if($flight->flight_duration || $flight->aircraft_type)
                                                <div style="font-size: 11px; color: #64748b; margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-family: Arial, sans-serif;">
                                                    @if($flight->flight_duration) <span>Duration: {{ $flight->flight_duration }}</span> @endif
                                                    <!-- @if($flight->aircraft_type) <span style="margin-left: 14px;">Aircraft: {{ $flight->aircraft_type }}</span> @endif -->
                                                </div>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Transit Banner Row -->
                                    @if($flight->transit_text)
                                        <tr>
                                            <td align="center" style="background-color: #f1f5f9; padding: 6px 10px; font-size: 11px; font-weight: bold; color: #334155; border-top: 1px solid #e2e8f0; text-align: center;">
                                                &#x1F550; {{ $flight->transit_text }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            @empty
                                <p style="font-style: italic; color: #64748b; font-size: 13px;">No flights attached to this booking.</p>
                            @endforelse

                                                        <!-- PURCHASE SUMMARY TABLE -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 24px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Purchase Summary
                            </div>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                <tr>
                                    <td style="width: 35%; font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Payment Type:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">Credit/Debit Card Authorization</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Card Holder Name:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_holder_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Card Type:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_type ?: 'Credit/Debit Card' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Card Number:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">XXXX-XXXX-XXXX-{{ $booking->card_last_4 ?: 'XXXX' }}</td>
                                </tr>
                                @if(!empty($booking->card_expiration))
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Expiration Date:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_expiration }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Billing Address:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->billing_address ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Phone Number:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->calling_number ?: ($booking->billing_phone ?: 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Email:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->email_address ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Total Amount:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;"><strong style="color: #0f172a;">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; background-color: #f8fafc; padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155;">Transaction Date:</td>
                                    <td style="padding: 8px 10px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->created_at ? $booking->created_at->format('M dS, Y') : date('M dS, Y') }}</td>
                                </tr>
                            </table>

                            @if($booking->bookingCards && $booking->bookingCards->count() > 0)
                                <!-- MULTIPLE AUTHORIZED CARDS ROSTER -->
                                <div style="font-size: 13px; font-weight: bold; color: #1e1b4b; margin-top: 16px; margin-bottom: 8px; font-family: Arial, sans-serif; text-transform: uppercase;">
                                    Authorized Payment Cards Roster ({{ $booking->bookingCards->count() + 1 }} Cards)
                                </div>
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 16px; font-family: Arial, sans-serif;">
                                    <thead>
                                        <tr style="background-color: #e0e7ff; color: #3730a3;">
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: left;">Card Holder</th>
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: left;">Type</th>
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: center;">Card Number</th>
                                            <th style="padding: 6px 8px; border: 1px solid #c7d2fe; text-align: center;">Expiration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; font-weight: bold;">{{ $booking->card_holder_name ?: 'Primary Card' }}</td>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $booking->card_type ?: 'Card' }}</td>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">XXXX-XXXX-XXXX-{{ $booking->card_last_4 }}</td>
                                            <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">{{ $booking->card_expiration ?: 'N/A' }}</td>
                                        </tr>
                                        @foreach($booking->bookingCards as $bCard)
                                            <tr>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $bCard->card_holder_name ?: 'Additional Card' }}</td>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b;">{{ $bCard->card_type ?: 'Card' }}</td>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">XXXX-XXXX-XXXX-{{ $bCard->card_last_4 }}</td>
                                                <td style="padding: 6px 8px; border: 1px solid #e2e8f0; color: #1e293b; text-align: center; font-family: monospace;">{{ $bCard->card_expiration ?: 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif


                            <!-- ACTION AUTHORIZATION BUTTON -->
                           
                            <!-- IMPORTANT INFORMATION & TERMS -->
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 28px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.03em; font-family: Arial, sans-serif;">
                                Important Information &amp; Terms
                            </div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.6; font-family: Arial, sans-serif;">
                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Please Note:</strong> Review the names, dates, cities, and departure/arrival times carefully.</p>
                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Important:</strong> Your e-tickets will be sent to you via email within 24 hours, or sooner if there is no delay from the airline’s side. Please note that fares are not guaranteed until payment is received and tickets are issued. If there are any restrictions, updates, or concerns from the airline, we will contact you via email or phone. If you wish to make any changes to this itinerary after the tickets have been issued, you will be responsible for any additional penalties, fare differences, and applicable fees.</p>
                                <p style="margin: 0 0 10px 0;">Baggage fees may apply. Please check with the airline for the most up-to-date baggage policies.</p>
                                
                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Note:</strong> As agreed, your credit card may be charged in split transactions, not exceeding the total amount. All transactions are for service fees and are 100% non-refundable. Airline tickets are non-refundable; however, you may be eligible for a refund within 24 hours of purchase, depending on the airline's policy.</p>

                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">Disclaimer:</strong> {{ $merchantName }} is an independent travel Agency with no third-party association. We shall not be associated or considered as an airline or an ally of any of the airlines or brands. {{ $merchantName }} is shown on your bank account details in most cases. However, sometimes we have to split the payment with the airline. {{ $merchantName }} and the airline or another company of that organization both will appear as recipients on your account. All the service fees and convenience fees are non-refundable.</p>

                                <p style="margin: 0 0 10px 0;"><strong style="color: #334155;">For Assistance:</strong> In case of any discrepancy and if an amendment is required, please feel free to contact us at +1 888-476-0932 or email us at reservation@travelomile.com within 24 hours and we will be happy to assist you.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Important Information:</h4>
                                <ul style="margin: 0 0 10px 0; padding-left: 20px;">
                                    <li style="margin-bottom: 4px;">Passenger names must be the same as on the passport (International Travel) OR any government-approved photo ID proof for Domestic travel.</li>
                                    <li style="margin-bottom: 4px;">We advise all passengers to ensure to have all travel documents including passports, and required visas issued and presented at the time of travel.</li>
                                    <li style="margin-bottom: 4px;">All passengers are recommended to be present at the airport 3 hours before departure for international departures, and 2 hours before domestic travel.</li>
                                    <li style="margin-bottom: 4px;">All international flights must be confirmed 72 hours before departure.</li>
                                    <li style="margin-bottom: 4px;">Review departure/arrival dates, times, origin/destination cities, stopovers, and connections.</li>
                                    <li style="margin-bottom: 4px;">Airline tickets are non-refundable, non-changeable, and non-cancellable in most cases. An airline may allow a ticket to be changed for a fee, plus the increased cost of the new ticket.</li>
                                </ul>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">For Changes Query:</h4>
                                <p style="margin: 0 0 10px 0;">Call us at +1 888-476-0932 to make any kind of changes in the itinerary. Any changes to the itinerary should be done prior to departure of the flight. The airline's rules will be quoted to the passenger before processing any modification to the itinerary which will include penalty, supplier fee and fare difference. Please note some reservations will be non-refundable and non-changeable. Additionally, once a change is processed the add collect will be non-refundable and non-transferable.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">For Cancellations and Refunds:</h4>
                                <p style="margin: 0 0 10px 0;">Call us at +1 888-476-0932. Booking should be cancelled at least 24 hours before the scheduled departure time of your flight to avoid a no-show. Cancellations can only be processed over the phone. Please note cancellation should be processed 24 hours prior to the departure of the flight. Additionally, some reservations will be non-refundable and non-changeable. Refund of any reservation will depend upon the fare rules of the ticketed fare and refund/cancellation penalty and supplier fees. Cancellation/refund penalty can be a new charge or can be adjusted from an existing ticket value based on the type of itinerary booked and fare rules involved. Any ticket refund after 24 hours of booking may take up to two billing cycles from the date of refund processed. If flights are not cancelled before scheduled departure time, the entire money gets fortified. Refunds are always issued to the original form of payment and refund credit will appear on one of the next two billing statements depending upon the bank processing time and the billing cycle of the credit card company. In some cases, it may be more depending upon airlines or consolidators involved and on type of booking.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Seat Assignments:</h4>
                                <p style="margin: 0 0 10px 0;">Most airlines have restricted rules for advance seat assignment and can only be done with a fee. Some fare restrictions only allow seat assignment at the airport during the time of check-in. Please refer to each operating airline for the most restricted rules. Call us at +1 888-476-0932 for seat assignment, if applicable.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Baggage Policy:</h4>
                                <p style="margin: 0 0 10px 0;">Your reservation may have a restricted baggage allowance and some airlines may charge an additional fee for each allowed checked-in or carry-on bag. Please refer to each operating airline for the most restricted rules. Call us at +1 888-476-0932 for baggage, if applicable.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Visa/Travel Documents:</h4>
                                <p style="margin: 0 0 10px 0;">All customers are advised to verify travel documents (transit visa/entry visa) for the country through which they are transiting or entering. We will not be responsible if proper travel documents are not available, and you are denied entry or transit into a Country. We request you to consult the embassy of the country(s) you are visiting or transiting through. Please visit TSA for any questions regarding this, as well as information on check-in procedures and airport security.</p>

                                <h4 style="font-size: 12px; color: #334155; margin: 14px 0 4px 0; text-transform: uppercase;">Check-In:</h4>
                                <p style="margin: 0 0 10px 0;">We recommend arriving at the airport 3 hours before your departure for international flights and 2 hours before your departure for domestic flights. For the most updated check-in rules, please contact airlines or TSA directly.</p>
                            </div>
                        </td>
                    </tr>

                    <!-- FOOTER & AGENT SIGNATURE -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0; font-family: Arial, sans-serif;">
                            <div style="font-weight: bold; margin-bottom: 4px; color: #1e293b;">Best Regards,</div>
                            <div style="color: #475569;">Reservation Desk</div>
                            <div style="font-weight: bold; color: #0f172a; margin-top: 4px;">{{ $agentName ?? ($booking->agent ? $booking->agent->name : 'Agent') }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Phone: +1 888-476-0932 || Ext: {{ $agentExt ?? '187' }}</div>
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

</body>
</html>
