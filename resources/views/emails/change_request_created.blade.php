<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Change Request</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f1f5f9; padding: 20px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div style="background-color: #0f172a; padding: 20px; text-align: center; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 0.05em; color: #38bdf8;">New Booking Change Request</h2>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 15px; margin-top: 0;">Hello Changes Team,</p>
            <p style="font-size: 14px; color: #475569;">
                A new change request has been submitted by Agent <strong>{{ $changeRequest->agent ? ($changeRequest->agent->alias_name ?: $changeRequest->agent->name) : 'System' }}</strong> for Booking Reference <strong style="color: #0284c7;">#{{ $changeRequest->booking->booking_id ?? $changeRequest->booking_id }}</strong>.
            </p>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 13px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: bold; border: 1px solid #e2e8f0; width: 35%;">Booking Ref:</td>
                    <td style="border: 1px solid #e2e8f0; font-weight: bold;">#{{ $changeRequest->booking->booking_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Airline PNR:</td>
                    <td style="border: 1px solid #e2e8f0; font-family: monospace; font-weight: bold; color: #0284c7;">{{ $changeRequest->booking->airline_pnr ?? 'N/A' }}</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Customer Name:</td>
                    <td style="border: 1px solid #e2e8f0;">{{ $changeRequest->booking->card_holder_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Customer Email:</td>
                    <td style="border: 1px solid #e2e8f0;">{{ $changeRequest->booking->email_address ?? 'N/A' }}</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Requested By (Agent):</td>
                    <td style="border: 1px solid #e2e8f0;">{{ $changeRequest->agent ? ($changeRequest->agent->alias_name ?: $changeRequest->agent->name) : 'N/A' }} ({{ $changeRequest->agent->email ?? 'N/A' }})</td>
                </tr>
                @if($changeRequest->request_type)
                <tr style="background-color: #f0f9ff;">
                    <td style="font-weight: bold; border: 1px solid #e2e8f0; color: #0284c7;">Request Type:</td>
                    <td style="border: 1px solid #e2e8f0; font-weight: bold; color: #0284c7;">{{ $changeRequest->request_type }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Assigned Date &amp; Time:</td>
                    <td style="border: 1px solid #e2e8f0;">{{ $changeRequest->assigned_at ? $changeRequest->assigned_at->format('M d, Y h:i A') : date('M d, Y h:i A') }}</td>
                </tr>
            </table>

            <div style="margin-top: 16px;">
                <div style="font-size: 13px; font-weight: bold; text-transform: uppercase; color: #0284c7; margin-bottom: 6px;">Change Request Details:</div>
                <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px; font-size: 13px; color: #0c4a6e; white-space: pre-wrap;">{{ $changeRequest->change_request_text }}</div>
            </div>

            @if($changeRequest->agent_remark)
            <div style="margin-top: 16px;">
                <div style="font-size: 13px; font-weight: bold; text-transform: uppercase; color: #475569; margin-bottom: 6px;">Agent Remarks:</div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; font-size: 13px; color: #334155; white-space: pre-wrap;">{{ $changeRequest->agent_remark }}</div>
            </div>
            @endif

            <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
                Please log into the Changes Queue Panel to review and update the status of this change request.
            </p>
        </div>
        <div style="background-color: #f8fafc; padding: 12px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
            CRM Reservations System • Changes Desk Notification
        </div>
    </div>
</body>
</html>
