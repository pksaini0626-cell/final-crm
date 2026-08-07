<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\CustomerAuthController;

class CustomerAuthMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public string $authUrl;
    public string $customSubject;
    public string $fromEmail;
    public string $fromName;
    public string $customNote;
    public string $agentName;
    public string $agentExt;
    public string $emailLanguage;
    public ?string $customHtml;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Booking $booking,
        ?string $customSubject = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        ?string $customNote = null,
        ?string $agentName = null,
        ?string $agentExt = null,
        string $emailLanguage = 'english',
        ?string $customHtml = null
    ) {
        $this->booking = $booking;
        $this->emailLanguage = strtolower($emailLanguage) === 'spanish' ? 'spanish' : 'english';
        $this->customHtml = $customHtml;
        
        $hash = CustomerAuthController::generateHash($booking);
        $this->authUrl = route('customer.authorize', ['booking' => $booking->id, 'hash' => $hash]);

        $airlineName = $booking->airline_name ?: ($booking->airline_code ?: 'Airline');
        $pnr = $booking->airline_pnr ?: ($booking->gk_pnr ?: $booking->booking_id);

        if ($this->emailLanguage === 'spanish') {
            $serviceNameEs = match($booking->service_provided) {
                'exchange' => 'Cambio de Vuelo',
                'cancellation' => 'Cancelación',
                'refund' => 'Reembolso',
                'seat_selection' => 'Selección de Asientos',
                'baggage_addition' => 'Edición de Equipaje',
                'others' => 'Servicio',
                'cancel_and_refund' => 'Cancelación y Reembolso',
                'name_correction' => 'Corrección de Nombre',
                'flight_upgrade' => 'Upgrade de Vuelo',
                'dob_correction' => 'Corrección de Fecha de Nacimiento',
                'pet_in_cabin' => 'Mascota en Cabina',
                'ancillary_refund' => 'Reembolso de Servicios Adicionales',
                default => 'Reserva de Vuelo'
            };
            $defaultSubject = "Autorización para {$airlineName} {$serviceNameEs} Confirmación #{$pnr}";
        } else {
            $serviceNameEn = match($booking->service_provided) {
                'exchange' => 'Exchange',
                'cancellation' => 'Cancellation',
                'refund' => 'Refund',
                'seat_selection' => 'Seat Selection',
                'baggage_addition' => 'Baggage Edition',
                'others' => 'Service',
                'cancel_and_refund' => 'Cancel and Refund',
                'name_correction' => 'Name Correction',
                'flight_upgrade' => 'Flight Upgrade',
                'dob_correction' => 'D.O.B Correction',
                'pet_in_cabin' => 'Pet In Cabin',
                'ancillary_refund' => 'Ancillary Refund',
                default => ucwords(str_replace('_', ' ', $booking->service_provided ?: 'Booking'))
            };
            $defaultSubject = "Authorization for {$airlineName} {$serviceNameEn} Booking Confirmation #{$pnr}";
        }

        $this->customSubject = $customSubject ?: $defaultSubject;
        $this->fromEmail = $fromEmail ?: (config('mail.from.address') ?: 'reservation@travelomile.com');
        $this->fromName = $fromName ?: 'Reservation Desk';
        $this->customNote = $customNote ?: '';
        $this->agentName = $agentName ?: ($booking->agent ? $booking->agent->name : 'Agent Desk');
        $this->agentExt = $agentExt ?: '187';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromEmail, $this->fromName),
            subject: $this->customSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if (!empty($this->customHtml)) {
            return new Content(
                htmlString: $this->customHtml,
            );
        }

        $viewName = $this->emailLanguage === 'spanish' 
            ? 'emails.customer_auth_email_es' 
            : 'emails.customer_auth_email';

        return new Content(
            view: $viewName,
        );
    }
}
