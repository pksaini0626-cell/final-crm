<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agent_id' => [
                'nullable',
                Rule::requiredIf(function () {
                    $user = auth()->user();
                    return $user && ($user->hasAnyRole(['admin', 'manager']) || in_array($user->role, ['admin', 'manager']));
                }),
                'exists:users,id',
            ],
            'booking_date' => 'required|date',
            'call_type' => 'required|in:meta,ppc,other',
            'vertical' => 'required|string|max:255',
            'trip_type' => 'nullable|in:one_way,round_trip,multi_city',
            'service_provided' => 'required|string|max:255',
            'booking_portal' => 'required|string|max:255',
            
            // PNR Details: At least one of airline_pnr or gk_pnr is MUST
            'gk_pnr' => 'required_without:airline_pnr|nullable|string|max:255',
            'airline_pnr' => 'required_without:gk_pnr|nullable|string|max:255',
            'airline_name' => 'nullable|string|max:255',
            'airline_code' => 'nullable|string|max:10',
            
            // Route Summary
            'from_airport' => 'nullable|string|max:10',
            'to_airport' => 'nullable|string|max:10',
            'from_city' => 'nullable|string|max:255',
            'to_city' => 'nullable|string|max:255',
            'travel_date' => 'nullable|date',
            'language' => 'nullable|string|max:255',
            
            // Billing & Customer Details: Card Last 4 and Email are MUST
            'card_holder_name' => 'nullable|string|max:255',
            'card_type' => 'nullable|string|max:255',
            'calling_number' => 'nullable|string|max:255',
            'billing_phone' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:1000',
            'card_last_4' => 'required|string|size:4',
            'card_expiration' => 'nullable|string|max:20',
            'email_address' => 'required|email|max:255',

            // Multiple Cards Array
            'booking_cards' => 'nullable|array',
            'booking_cards.*.card_holder_name' => 'nullable|string|max:255',
            'booking_cards.*.card_type' => 'nullable|string|max:255',
            'booking_cards.*.card_last_4' => 'required_with:booking_cards|string|size:4',
            'booking_cards.*.card_expiration' => 'nullable|string|max:20',
            
            // Status Tracking (Auto-assigned to booking_generated if empty)
            'booking_status' => 'nullable|in:booking_generated,email_auth_sent,email_auth_done,ticketed,booking_complete,void',
            'case_status' => 'nullable|required_if:booking_status,void|in:rdr,retrieval,chargeback,refund,void',
            'email_auth_taken' => 'nullable|boolean',
            
            // Financials
            'currency' => 'required|string|max:3',
            'merchant' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'paid_to_airline' => 'required|numeric|min:0',
            'total_mco' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,received,refund,cancelled',
            'payment_info' => 'nullable|string',

            // Passengers: At least one passenger is MUST
            'passengers' => 'required|array|min:1',
            'passengers.*.first_name' => 'required|string|max:255',
            'passengers.*.last_name' => 'required|string|max:255',
            'passengers.*.title' => 'nullable|string|max:255',
            'passengers.*.dob' => 'required|date',
            'passengers.*.pax_index' => 'nullable|string|max:10',
            'passengers.*.ticket_number' => 'nullable|string|max:255',
            'passengers.*.seat_number' => 'nullable|string|max:255',

            // Nested validation for Flights
            'flights' => 'nullable|array',
            'flights.*.flight_number' => 'required_with:flights|string|max:255',
            'flights.*.operating_carrier' => 'nullable|string|max:255',
            'flights.*.airline_name' => 'nullable|string|max:255',
            'flights.*.airline_logo' => 'nullable|string|max:500',
            'flights.*.operated_by' => 'nullable|string|max:255',
            'flights.*.operated_by_logo' => 'nullable|string|max:500',
            'flights.*.origin_airport' => 'required_with:flights|string|max:10',
            'flights.*.origin_city' => 'nullable|string|max:255',
            'flights.*.origin_airport_name' => 'nullable|string|max:255',
            'flights.*.destination_airport' => 'required_with:flights|string|max:10',
            'flights.*.destination_city' => 'nullable|string|max:255',
            'flights.*.destination_airport_name' => 'nullable|string|max:255',
            'flights.*.departure_time' => 'nullable|date',
            'flights.*.arrival_time' => 'nullable|date',
            'flights.*.booking_class' => 'nullable|string|max:5',
            'flights.*.cabin' => 'nullable|string|max:255',
            'flights.*.aircraft_type' => 'nullable|string|max:255',
            'flights.*.status' => 'nullable|string|max:255',
            'flights.*.flight_duration' => 'nullable|string|max:255',
            'flights.*.day_offset' => 'nullable|integer',
            'flights.*.transit_text' => 'nullable|string|max:500',

            // Optional Initial Remark
            'initial_remark' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'passengers.required' => 'At least one passenger details are required to create a booking.',
            'passengers.min' => 'At least one passenger details are required to create a booking.',
            'passengers.*.first_name.required' => 'Passenger First Name is required.',
            'passengers.*.last_name.required' => 'Passenger Last Name is required.',
            'gk_pnr.required_without' => 'At least one of Airline PNR or GK PNR is required.',
            'airline_pnr.required_without' => 'At least one of Airline PNR or GK PNR is required.',
            'card_last_4.required' => 'Card Last 4 Digits are required.',
            'card_last_4.size' => 'Card Last 4 Digits must be exactly 4 characters.',
            'email_address.required' => 'Customer Email Address is required.',
        ];
    }
}
