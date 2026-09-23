<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Authorization & Payment Failed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; margin: 0; padding: 20px;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <!-- HEADER -->
        <tr>
            <td style="background-color: #dc2626; padding: 20px 25px; text-align: left;">
                <h1 style="color: #ffffff; font-size: 20px; font-weight: bold; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">
                    ❌ Customer Authorization &amp; Charge Failed
                </h1>
                <p style="color: #fee2e2; font-size: 13px; margin: 5px 0 0 0;">
                    Booking Reference #{{ $booking->booking_id }}
                </p>
            </td>
        </tr>

        <!-- CONTENT -->
        <tr>
            <td style="padding: 25px;">
                <p style="font-size: 15px; margin-top: 0;">
                    Hello <strong>{{ $booking->agent ? ($booking->agent->alias_name ?: $booking->agent->name) : 'Agent' }}</strong>,
                </p>

                <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; border: 1px solid #fecaca; border-radius: 6px; padding: 14px 16px; margin: 15px 0;">
                    <p style="font-size: 14px; color: #991b1b; margin: 0; font-weight: 600;">
                        ⚠️ Customer authorization for your booking <strong>#{{ $booking->booking_id }}</strong> was cancelled / payment charge failed. The charge amount has <u>NOT</u> been charged from the customer and the booking status has been updated to <strong>FAILED</strong>.
                    </p>
                </div>

                <!-- DETAILS BOX -->
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <h3 style="margin: 0 0 12px 0; font-size: 13px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                        Booking &amp; Transaction Details
                    </h3>
                    <table width="100%" border="0" cellpadding="5" cellspacing="0" style="font-size: 13px; color: #1e293b;">
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
                            <td>{{ $booking->airline_name }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Customer Name:</td>
                            <td style="font-weight: bold;">{{ $booking->card_holder_name ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Customer Email:</td>
                            <td>{{ $booking->email_address ?: 'N/A' }}</td>
                        </tr>
                        @if($booking->calling_number || $booking->billing_phone)
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Customer Phone:</td>
                            <td>{{ $booking->calling_number ?: $booking->billing_phone }}</td>
                        </tr>
                        @endif
                        @if($booking->passengers && $booking->passengers->count() > 0)
                        <tr>
                            <td style="font-weight: bold; color: #64748b; vertical-align: top;">Passengers:</td>
                            <td>
                                @foreach($booking->passengers as $pax)
                                    <div>{{ $pax->title }} {{ $pax->first_name }} {{ $pax->last_name }}</div>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Charge Amount:</td>
                            <td style="font-weight: bold; color: #dc2626; font-size: 14px;">
                                {{ $booking->currency ?: 'USD' }} ${{ number_format((float)$booking->total_amount, 2) }}
                                <span style="font-size: 11px; font-weight: normal; color: #dc2626; background: #fee2e2; padding: 2px 6px; border-radius: 3px; margin-left: 5px;">NOT CHARGED</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Total MCO:</td>
                            <td style="font-weight: bold; color: #475569;">
                                {{ $booking->currency ?: 'USD' }} ${{ number_format((float)$booking->total_mco, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Booking Status:</td>
                            <td>
                                <span style="background-color: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase;">
                                    FAILED
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #64748b; vertical-align: top;">Failure Reason:</td>
                            <td style="color: #b91c1c; font-style: italic;">
                                {{ $reason ?: 'Payment charge could not be processed / authorization cancelled by Admin.' }}
                            </td>
                        </tr>
                        @if($cancelledBy)
                        <tr>
                            <td style="font-weight: bold; color: #64748b;">Processed By:</td>
                            <td>{{ strtoupper($cancelledBy->role) }} ({{ $cancelledBy->alias_name ?: $cancelledBy->name }})</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- SUPPORT NOTICE -->
                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 14px 16px; margin: 20px 0;">
                    <p style="font-size: 13px; color: #1e40af; margin: 0;">
                        ℹ️ <strong>Need assistance?</strong> For any further support kindly contact with Admin team.
                    </p>
                </div>

                <!-- CALL TO ACTION BUTTON -->
                <div style="text-align: center; margin: 25px 0 10px 0;">
                    <a href="{{ route('bookings.index') }}?q={{ $booking->booking_id }}" style="background-color: #334155; color: #ffffff; text-decoration: none; padding: 10px 22px; border-radius: 6px; font-weight: bold; font-size: 13px; display: inline-block;">
                        View Booking on CRM &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td style="background-color: #f1f5f9; padding: 15px 25px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
                Flight CRM System &bull; Automatic Agent Notification
            </td>
        </tr>
    </table>
</body>
</html>
