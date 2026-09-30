<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Refund / Void Request</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; padding: 25px; color: #1e293b; line-height: 1.5;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 24px; text-align: center; border-bottom: 3px solid #ef4444;">
            <div style="display: inline-block; padding: 6px 14px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 20px; color: #fca5a5; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">
                Action Required • Review &amp; Approval
            </div>
            <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">
                New {{ $refundRequest->formatted_request_type }} Request
            </h1>
            <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 13px;">
                Assigned to MIS Team, MIS Manager, Admin &amp; Super Admin
            </p>
        </div>

        <div style="padding: 28px 24px;">
            <p style="font-size: 15px; margin-top: 0; color: #334155;">
                Hello Team,
            </p>
            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                Agent <strong>{{ $refundRequest->agent ? ($refundRequest->agent->alias_name ?: $refundRequest->agent->name) : 'An Agent' }}</strong> has submitted a new customer <strong>{{ strtolower($refundRequest->formatted_request_type) }} request</strong> for Booking Reference <strong style="color: #0284c7;">#{{ $refundRequest->booking->booking_id ?? 'N/A' }}</strong>.
            </p>

            <!-- Key Financial Highlights Banner -->
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; border-right: 1px solid #fee2e2; padding-right: 12px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #991b1b; letter-spacing: 0.05em; margin-bottom: 4px;">
                                Total MCO (Merchant Value)
                            </div>
                            <div style="font-size: 20px; font-weight: 800; color: #0f172a; font-family: monospace;">
                                {{ $refundRequest->currency }} {{ number_format((float)($refundRequest->booking->total_mco ?? 0), 2) }}
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Max refundable amount</div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding-left: 16px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #b91c1c; letter-spacing: 0.05em; margin-bottom: 4px;">
                                Requested Refund Amount
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: #dc2626; font-family: monospace;">
                                {{ $refundRequest->currency }} {{ number_format((float)$refundRequest->refund_amount, 2) }}
                            </div>
                            <div style="font-size: 11px; font-weight: 600; color: #b91c1c; margin-top: 2px;">
                                Type: {{ $refundRequest->formatted_request_type }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Booking & Customer Snapshot Table -->
            <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse: collapse; font-size: 13px; margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569; width: 35%; border-bottom: 1px solid #e2e8f0;">Booking ID:</td>
                    <td style="font-weight: 800; color: #0284c7; font-family: monospace; border-bottom: 1px solid #e2e8f0;">#{{ $refundRequest->booking->booking_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Customer / Card Holder:</td>
                    <td style="font-weight: 600; color: #0f172a; border-bottom: 1px solid #e2e8f0;">{{ $refundRequest->booking->card_holder_name ?? 'N/A' }}</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Customer Email &amp; Phone:</td>
                    <td style="color: #334155; border-bottom: 1px solid #e2e8f0;">
                        {{ $refundRequest->booking->email_address ?? 'N/A' }}<br>
                        <span style="font-size: 12px; color: #64748b;">{{ $refundRequest->booking->billing_phone ?: ($refundRequest->booking->calling_number ?: 'N/A') }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Airline PNR:</td>
                    <td style="font-family: monospace; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0;">
                        {{ $refundRequest->booking->airline_pnr ?: ($refundRequest->booking->gk_pnr ?: 'N/A') }}
                    </td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Merchant Profile:</td>
                    <td style="color: #334155; border-bottom: 1px solid #e2e8f0;">
                        {{ $refundRequest->booking->merchantProfile->name ?? ($refundRequest->booking->merchant ?? 'N/A') }}
                        @if($refundRequest->booking && $refundRequest->booking->merchantProfile && ($refundRequest->booking->merchantProfile->merchant_code || $refundRequest->booking->merchantProfile->code))
                            <span style="font-family: monospace; font-size: 11px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">
                                {{ $refundRequest->booking->merchantProfile->merchant_code ?: $refundRequest->booking->merchantProfile->code }}
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Request Raised By (Agent):</td>
                    <td style="color: #334155; border-bottom: 1px solid #e2e8f0;">
                        <strong>{{ $refundRequest->agent ? ($refundRequest->agent->alias_name ?: $refundRequest->agent->name) : 'N/A' }}</strong> 
                        <span style="font-size: 12px; color: #64748b;">({{ $refundRequest->agent->email ?? 'N/A' }})</span>
                    </td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="font-weight: 700; color: #475569;">Refund Date:</td>
                    <td style="color: #0f172a; font-weight: 600;">
                        {{ $refundRequest->refund_date ? $refundRequest->refund_date->format('M d, Y') : date('M d, Y') }}
                    </td>
                </tr>
            </table>

            <!-- Reason Section -->
            <div style="margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Reason for Refund:
                </div>
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #ef4444; border-radius: 4px; padding: 12px 14px; font-size: 13px; color: #0f172a; font-weight: 600;">
                    {{ $refundRequest->reason_for_refund }}
                </div>
            </div>

            <!-- Mandatory Agent Remarks Section -->
            <div style="margin-bottom: 26px;">
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Agent Remarks:
                </div>
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #3b82f6; border-radius: 4px; padding: 12px 14px; font-size: 13px; color: #334155; white-space: pre-wrap;">{{ $refundRequest->remarks }}</div>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0 10px 0;">
                <a href="{{ url('/admin/refunds') }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 6px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);">
                    Review &amp; Action Request in CRM &rarr;
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
            CRM Reservations System • Refund &amp; Void Desk Notification<br>
            Please do not reply directly to this automated email.
        </div>
    </div>
</body>
</html>
