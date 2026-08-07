<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangeRequestCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ChangeRequest $changeRequest;

    /**
     * Create a new message instance.
     */
    public function __construct(ChangeRequest $changeRequest)
    {
        $this->changeRequest = $changeRequest->load(['booking', 'agent']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ref = $this->changeRequest->booking ? $this->changeRequest->booking->booking_id : $this->changeRequest->booking_id;
        return new Envelope(
            subject: "New Change Request Assigned for Booking #{$ref}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.change_request_created',
        );
    }
}
