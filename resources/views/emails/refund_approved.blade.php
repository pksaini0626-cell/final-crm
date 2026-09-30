<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Refund / Void Request Approved</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; padding: 25px; color: #1e293b; line-height: 1.5;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #064e3b 0%, #065f46 100%); padding: 24px; text-align: center; border-bottom: 3px solid #10b981;">
            <div style="display: inline-block; padding: 6px 14px; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.5); border-radius: 20px; color: #a7f3d0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">
                Request Approved &amp; Processed
            </div>
            <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">
                {{ $refundRequest->formatted_request_type }} Request Approved
            </h1>
            <p style="margin: 6px 0 0 0; color: #cbd5e1; font-size: 13px;">
                Customer refund value has been processed on gateway
            </p>
        </div>

        <div style="padding: 28px 24px;">
            <p style="font-size: 15px; margin-top: 0; color: #334155;">
                Hello <strong>{{ $refundRequest->agent ? ($refundRequest->agent->alias_name ?: $refundRequest->agent->name) : 'Agent' }}</strong>,
            </p>
            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                Your customer refund/void request for Booking Reference <strong style="color: #0284c7;">#{{ $refundRequest->booking->booking_id ?? 'N/A' }}</strong> has been <strong>approved and actioned</strong>. The customer refund value has been refunded.
            </p>

            <!-- Refund Highlight Card -->
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 18px; margin-bottom: 24px; text-align: center;">
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #047857; letter-spacing: 0.05em; margin-bottom: 4px;">
                    Amount Refunded to Customer
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #065f46; font-family: monospace;">
                    {{ $refundRequest->currency }} {{ number_format((float)$refundRequest->refund_amount, 2) }}
                </div>
                <div style="display: inline-block; margin-top: 8px; font-size: 12px; font-weight: 700; color: #065f46; background: #d1fae5; padding: 3px 12px; border-radius: 12px;">
                    Updated Booking Payment Status: {{ strtoupper(str_replace('_', ' ', $refundRequest->booking->payment_status ?? $refundRequest->request_type)) }}
                </div>
            </div>

            <!-- Summary Details Table -->
            <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse: collapse; font-size: 13px; margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569; width: 35%; border-bottom: 1px solid #e2e8f0;">Booking ID:</td>
                    <td style="font-weight: 800; color: #0284c7; font-family: monospace; border-bottom: 1px solid #e2e8f0;">#{{ $refundRequest->booking->booking_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Customer Name:</td>
                    <td style="font-weight: 600; color: #0f172a; border-bottom: 1px solid #e2e8f0;">{{ $refundRequest->booking->card_holder_name ?? 'N/A' }}</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Airline PNR:</td>
                    <td style="font-family: monospace; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0;">
                        {{ $refundRequest->booking->airline_pnr ?: ($refundRequest->booking->gk_pnr ?: 'N/A') }}
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Request Type:</td>
                    <td style="font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0;">
                        {{ $refundRequest->formatted_request_type }}
                    </td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Actioned / Approved By:</td>
                    <td style="color: #065f46; font-weight: 700; border-bottom: 1px solid #e2e8f0;">
                        {{ $approver ? ($approver->alias_name ?: $approver->name) : ($refundRequest->approver ? ($refundRequest->approver->alias_name ?: $refundRequest->approver->name) : 'Management') }}
                        @if($approver)
                            <span style="font-size: 11px; font-weight: normal; color: #64748b;">({{ strtoupper(str_replace('_', ' ', $approver->role)) }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Approved Date &amp; Time:</td>
                    <td style="color: #334155; border-bottom: 1px solid #e2e8f0;">
                        {{ $refundRequest->approved_at ? $refundRequest->approved_at->format('M d, Y h:i A') : date('M d, Y h:i A') }}
                    </td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569;">Updated on MCO Sheet:</td>
                    <td style="color: #065f46; font-weight: 600;">Yes (Status: {{ $refundRequest->booking->payment_status ?? $refundRequest->request_type }})</td>
                </tr>
            </table>

            <!-- Approver Remarks / MIS Remarks Section -->
            @if($refundRequest->admin_remarks || $refundRequest->mis_remarks)
            <div style="margin-bottom: 24px;">
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #065f46; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Approval Remarks &amp; Instructions:
                </div>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #10b981; border-radius: 4px; padding: 14px; font-size: 13px; color: #14532d; white-space: pre-wrap;">
                    @if($refundRequest->admin_remarks)
                        <strong>Approver Notes:</strong> {{ $refundRequest->admin_remarks }}
                    @endif
                    @if($refundRequest->mis_remarks)
                        @if($refundRequest->admin_remarks)<br><br>@endif
                        <strong>MIS Notes:</strong> {{ $refundRequest->mis_remarks }}
                    @endif
                </div>
            </div>
            @endif

            <!-- Original Request Reason & Agent Remarks Snapshot -->
            <div style="margin-bottom: 24px;">
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Your Original Request Remarks:
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px; font-size: 13px; color: #475569; white-space: pre-wrap;">{{ $refundRequest->remarks }}</div>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0 10px 0;">
                <a href="{{ route('bookings.index', ['search' => $refundRequest->booking->booking_id ?? '']) }}" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 6px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3);">
                    View Booking in CRM &rarr;
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
            CRM Reservations System • Refund Desk Notification<br>
            Please do not reply directly to this automated email.
        </div>
    </div>
</body>
</html>
