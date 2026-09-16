<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export bookings to CSV using active filter parameters.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Booking::with(['agent', 'merchantProfile']);

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->input('date_to'));
        }

        // Agent filter
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        // Booking status filter
        if ($request->filled('booking_status')) {
            $query->where('booking_status', $request->input('booking_status'));
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Merchant filter
        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->input('merchant_id'));
        }

        // Service provided filter
        if ($request->filled('service_provided')) {
            $query->where('service_provided', $request->input('service_provided'));
        }

        // General search query
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'like', "%{$search}%")
                  ->orWhere('airline_pnr', 'like', "%{$search}%")
                  ->orWhere('gk_pnr', 'like', "%{$search}%")
                  ->orWhere('card_holder_name', 'like', "%{$search}%")
                  ->orWhere('email_address', 'like', "%{$search}%")
                  ->orWhere('calling_number', 'like', "%{$search}%");
            });
        }

        $filename = 'bookings-export-' . date('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Booking ID',
                'Agent Alias',
                'Date',
                'Service',
                'Portal',
                'Airline Name',
                'PNRs',
                'Customer Name',
                'Email',
                'Total Amount',
                'Paid to Airline',
                'Total MCO',
                'Booking Status',
                'Payment Status',
                'Case Status',
                'Created At'
            ]);

            // Stream rows in chunks
            $query->latest()->chunk(200, function ($bookings) use ($handle) {
                foreach ($bookings as $b) {
                    fputcsv($handle, [
                        $b->booking_id,
                        $b->agent ? $b->agent->alias_name : 'N/A',
                        $b->booking_date ? $b->booking_date->format('Y-m-d') : '',
                        $b->service_provided,
                        $b->booking_portal,
                        $b->airline_name ?: 'N/A',
                        "Airline: " . ($b->airline_pnr ?: 'N/A') . " / GK: " . ($b->gk_pnr ?: 'N/A'),
                        $b->card_holder_name ?: 'N/A',
                        $b->email_address,
                        number_format($b->total_amount, 2, '.', ''),
                        number_format($b->paid_to_airline, 2, '.', ''),
                        number_format($b->reportable_mco, 2, '.', ''),
                        $b->booking_status,
                        $b->payment_status,
                        $b->case_status ?: 'N/A',
                        $b->created_at ? $b->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
