<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Ticketing Assignment</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #222222; margin: 0; padding: 20px 10px; line-height: 1.5;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9; width: 100%; margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 10px 0;">
                <table width="640" border="0" cellpadding="0" cellspacing="0" style="max-width: 640px; width: 100%; background-color: #ffffff; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <tr>
                        <td align="center" style="background-color: #0f172a; color: #ffffff; padding: 24px 30px; text-align: center;">
                            <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #38bdf8; letter-spacing: 0.05em; margin-bottom: 4px;">
                                Flight CRM Ticketing Desk
                            </div>
                            <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff;">New Booking Assignment Alert</h1>
                            <div style="margin-top: 6px; font-size: 13px; color: #94a3b8;">
                                Booking Reference: <strong style="color: #ffffff;">#{{ $booking->booking_id }}</strong>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 25px 30px; background-color: #ffffff;">
                            <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 12px;">
                                Hello Ticketing Team,
                            </div>
                            <div style="font-size: 14px; color: #334155; margin-bottom: 16px;">
                                A booking with completed email authorization has been assigned to you for e-ticket generation and customer dispatch.
                            </div>

                            <!-- ASSIGNMENT DETAILS CARD -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 14px 18px; font-size: 13px; color: #1e293b;">
                                        <div style="margin-bottom: 6px;"><strong>Booking ID:</strong> #{{ $booking->booking_id }}</div>
                                        <div style="margin-bottom: 6px;"><strong>Airline PNR:</strong> {{ $booking->airline_pnr ?: 'N/A' }}</div>
                                        <div style="margin-bottom: 6px;"><strong>Customer Email:</strong> {{ $booking->email_address }}</div>
                                        <div style="margin-bottom: 6px;"><strong>Customer Phone:</strong> {{ $booking->calling_number ?: $booking->billing_phone ?: 'N/A' }}</div>
                                        <div style="margin-bottom: 6px;"><strong>Merchant:</strong> {{ $booking->merchantProfile ? $booking->merchantProfile->name : 'N/A' }}</div>
                                        <div><strong>Assigned By:</strong> {{ $assignedBy ? ($assignedBy->alias_name ?: $assignedBy->name) : 'System/Admin' }}</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- PASSENGERS LIST -->
                            <div style="font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 8px; text-transform: uppercase;">
                                Passengers To Ticket:
                            </div>
                            <ul style="margin: 0 0 20px 0; padding-left: 20px; font-size: 13px; color: #334155;">
                                @foreach($booking->passengers as $pax)
                                    <li>{{ $pax->title }} {{ $pax->first_name }} {{ $pax->last_name }} (Seat: {{ $pax->seat_number ?: 'N/A' }}, Ticket: {{ $pax->ticket_number ?: 'Pending' }})</li>
                                @endforeach
                            </ul>

                            <!-- ACTION BUTTON -->
                            <div style="text-align: center; margin: 24px 0;">
                                <a href="{{ route('manager.tickets.preview-email', $booking) }}" style="background-color: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">
                                    Open Ticketing Preview &amp; Issue E-Ticket &rarr;
                                </a>
                            </div>

                            <div style="font-size: 12px; color: #64748b; margin-top: 20px; text-align: center;">
                                Log into Flight CRM to manage ticket numbers, view live email preview, and issue e-tickets.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
