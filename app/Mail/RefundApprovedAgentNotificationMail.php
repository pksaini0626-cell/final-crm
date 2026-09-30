<?php

namespace App\Mail;

use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundApprovedAgentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public RefundRequest $refundRequest;
    public ?User $approver;

    /**
     * Create a new message instance.
     */
    public function __construct(RefundRequest $refundRequest, ?User $approver = null)
    {
        $this->refundRequest = $refundRequest->loadMissing([
            'booking.agent',
            'booking.merchantProfile',
            'booking.passengers',
            'agent',
            'approver'
        ]);
        $this->approver = $approver ?? $refundRequest->approver;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $booking = $this->refundRequest->booking;
        $ref = $booking ? $booking->booking_id : $this->refundRequest->booking_id;
        $typeLabel = $this->refundRequest->formatted_request_type;
        $currency = $this->refundRequest->currency ?: 'USD';
        $amount = number_format((float)$this->refundRequest->refund_amount, 2);

        return new Envelope(
            subject: "APPROVED: {$typeLabel} Request for Booking #{$ref} ({$currency} {$amount})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.refund_approved',
        );
    }
}
