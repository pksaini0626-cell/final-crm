<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $statusLabel }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f1f5f9; padding: 20px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div style="background-color: {{ $changeRequest->status === 'completed' ? '#059669' : '#0284c7' }}; padding: 20px; text-align: center; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 0.05em;">{{ $statusLabel }}</h2>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 15px; margin-top: 0;">Hello <strong>{{ $changeRequest->agent ? ($changeRequest->agent->alias_name ?: $changeRequest->agent->name) : 'Agent' }}</strong>,</p>
            
            @if($changeRequest->status === 'working')
                <p style="font-size: 14px; color: #0369a1; background-color: #e0f2fe; padding: 12px; border-radius: 6px; border-left: 4px solid #0284c7;">
                    ℹ️ The Changes Team has started working on your change request for Booking Reference <strong>#{{ $changeRequest->booking->booking_id ?? $changeRequest->booking_id }}</strong>.
                </p>
            @elseif($changeRequest->status === 'completed')
                <p style="font-size: 14px; color: #047857; background-color: #d1fae5; padding: 12px; border-radius: 6px; border-left: 4px solid #10b981;">
                    ✅ The requested changes on Booking Reference <strong>#{{ $changeRequest->booking->booking_id ?? $changeRequest->booking_id }}</strong> have been <strong>COMPLETED</strong> successfully.
                </p>
            @else
                <p style="font-size: 14px; color: #475569;">
                    The status of your change request for Booking Reference <strong>#{{ $changeRequest->booking->booking_id ?? $changeRequest->booking_id }}</strong> has been updated to <strong>{{ strtoupper($changeRequest->status) }}</strong>.
                </p>
            @endif

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 13px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: bold; border: 1px solid #e2e8f0; width: 35%;">Booking Reference:</td>
                    <td style="border: 1px solid #e2e8f0; font-weight: bold;">#{{ $changeRequest->booking->booking_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Status:</td>
                    <td style="border: 1px solid #e2e8f0; font-weight: bold; color: {{ $changeRequest->status === 'completed' ? '#059669' : '#0284c7' }}; text-transform: uppercase;">{{ $changeRequest->status }}</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Processed By:</td>
                    <td style="border: 1px solid #e2e8f0;">{{ $changeRequest->changesAgent ? ($changeRequest->changesAgent->alias_name ?: $changeRequest->changesAgent->name) : 'Changes Desk' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; border: 1px solid #e2e8f0;">Update Date &amp; Time:</td>
                    <td style="border: 1px solid #e2e8f0;">{{ date('M d, Y h:i A') }}</td>
                </tr>
            </table>

            <div style="margin-top: 16px;">
                <div style="font-size: 13px; font-weight: bold; text-transform: uppercase; color: #334155; margin-bottom: 6px;">Original Change Request:</div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; font-size: 13px; color: #475569; white-space: pre-wrap;">{{ $changeRequest->change_request_text }}</div>
            </div>

            @if($changeRequest->changes_remark)
            <div style="margin-top: 16px;">
                <div style="font-size: 13px; font-weight: bold; text-transform: uppercase; color: {{ $changeRequest->status === 'completed' ? '#059669' : '#0284c7' }}; margin-bottom: 6px;">Changes Team Remarks:</div>
                <div style="background: {{ $changeRequest->status === 'completed' ? '#f0fdf4' : '#f0f9ff' }}; border-left: 4px solid {{ $changeRequest->status === 'completed' ? '#10b981' : '#0284c7' }}; padding: 12px; font-size: 13px; color: #1e293b; white-space: pre-wrap;">{{ $changeRequest->changes_remark }}</div>
            </div>
            @endif

            <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
                Log into the CRM dashboard to view full booking details.
            </p>
        </div>
        <div style="background-color: #f8fafc; padding: 12px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
            CRM Reservations System • Changes Desk Notification
        </div>
    </div>
</body>
</html>
