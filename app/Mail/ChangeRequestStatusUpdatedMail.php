<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangeRequestStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ChangeRequest $changeRequest;
    public string $statusLabel;

    /**
     * Create a new message instance.
     */
    public function __construct(ChangeRequest $changeRequest)
    {
        $this->changeRequest = $changeRequest->load(['booking', 'agent', 'changesAgent']);
        $this->statusLabel = match ($changeRequest->status) {
            'working' => 'Changes Team is Working on Booking',
            'completed' => 'Work Completed on Booking',
            'cancelled' => 'Change Request Cancelled',
            default => 'Change Request Updated',
        };
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ref = $this->changeRequest->booking ? $this->changeRequest->booking->booking_id : $this->changeRequest->booking_id;
        return new Envelope(
            subject: "{$this->statusLabel} #{$ref}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.change_request_status_updated',
        );
    }
}
