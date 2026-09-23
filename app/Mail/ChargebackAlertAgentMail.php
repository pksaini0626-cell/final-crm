<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Models\ChargebackControl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChargebackAlertAgentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public ?ChargebackControl $chargeback;
    public ?string $oldDisputeType;
    public ?string $newDisputeType;
    public ?string $oldStatus;
    public ?string $newStatus;
    public ?User $changedBy;
    public ?string $note;
    public string $disputeTime;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Booking $booking,
        ?string $oldDisputeType = null,
        ?string $newDisputeType = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?User $changedBy = null,
        ?string $note = null,
        ?ChargebackControl $chargeback = null
    ) {
        $this->booking = $booking;
        $this->oldDisputeType = $oldDisputeType;
        $this->newDisputeType = $newDisputeType;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
        $this->note = $note;
        $this->chargeback = $chargeback;
        $this->disputeTime = now()->format('d M Y, h:i A');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $pnr = $this->booking->airline_pnr ?: ($this->booking->gk_pnr ?: ($this->chargeback?->pnr ?: 'N/A'));
        $agentName = $this->booking->agent
            ? ($this->booking->agent->alias_name ?: $this->booking->agent->name)
            : ($this->chargeback?->agent_name ?: 'Agent');

        // Subject format: CB Alert for PNR- JQGNZ9, Agent - Gilbert, (dispute time)
        $subject = "CB Alert for PNR- {$pnr}, Agent - {$agentName}, ({$this->disputeTime})";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.chargeback_agent_alert',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
