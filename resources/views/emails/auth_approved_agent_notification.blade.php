<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Authorization Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; margin: 0; padding: 20px;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <!-- HEADER -->
        <tr>
            <td style="background-color: #059669; padding: 20px 25px; text-align: left;">
                <h1 style="color: #ffffff; font-size: 20px; font-weight: bold; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">
                    ✅ Customer Authorization Approved
                </h1>
                <p style="color: #d1fae5; font-size: 13px; margin: 5px 0 0 0;">
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

                <p style="font-size: 14px; color: #334155;">
                    Great news! The customer email authorization for your booking reference <strong>#{{ $booking->booking_id }}</strong> has been officially approved by 
                    <strong style="color: #047857;">{{ $approvedBy ? strtoupper($approvedBy->role) . ' (' . ($approvedBy->alias_name ?: $approvedBy->name) . ')' : 'Admin' }}</strong>.
                </p>

                <div style="background-color: #f0fdf4; border-left: 4px solid #10b981; border: 1px solid #d1fae5; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #065f46; text-transform: uppercase;">
                        Booking Authorization Details
                    </h3>
                    <table width="100%" border="0" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #1e293b;">
                        <tr>
                            <td style="width: 35%; font-weight: bold; color: #475569;">Booking Reference:</td>
                            <td style="font-family: monospace; font-weight: bold; color: #0284c7;">#{{ $booking->booking_id }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #475569;">Customer Name:</td>
                            <td style="font-weight: bold;">{{ $booking->card_holder_name ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #475569;">Customer Email:</td>
                            <td>{{ $booking->email_address }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #475569;">Airline PNR:</td>
                            <td style="font-family: monospace; font-weight: bold; color: #d97706;">{{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #475569;">Total Amount:</td>
                            <td style="font-weight: bold; color: #059669;">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #475569;">Current Status:</td>
                            <td>
                                <span style="background-color: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase;">
                                    EMAIL AUTH DONE
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                <p style="font-size: 14px; color: #334155;">
                    The booking status has been updated to <strong>EMAIL AUTH DONE</strong> and is now ready for ticket generation or assignment to the ticketing team.
                </p>

                <!-- CALL TO ACTION BUTTON -->
                <div style="text-align: center; margin: 30px 0 15px 0;">
                    <a href="{{ route('bookings.index') }}?search={{ $booking->booking_id }}" style="background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 2px 4px rgba(79,70,229,0.3);">
                        View Booking on CRM Dashboard &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td style="background-color: #f1f5f9; padding: 15px 25px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
                Flight CRM System &bull; Automatic Agent Authorization Notification
            </td>
        </tr>
    </table>
</body>
</html>
