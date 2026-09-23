<?php

namespace App\Http\Controllers\Chargeback;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChargebackControl;
use App\Models\ChargebackPortal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChargebackCsvController extends Controller
{
    /**
     * Exact canonical CSV headers matching the reference consolidated spreadsheet.
     */
    protected array $canonicalHeaders = [
        'Portal',
        'Received Date',
        'Received Month',
        'Booking Date',
        'Booking Month',
        'Deadline Date',
        'Action Taken Date',
        'CBK Status',
        'Case',
        'Dispute Type',
        'Current status',
        'PNR',
        'AgentName',
        'Currency',
        'Total booking amount',
        'Disputed Amount ($)',
        'Case Number',
        'CC Brand',
        'Card No.',
        'Reason Code',
        'Reason code/description for charge back',
        'Verticle',
        'Remarks',
        'Passenger ',
        'Service provided',
        'Shift time ',
        'Shift Month',
        'Statemen Month ',
        'sds',
    ];

    /**
     * Show the upload CSV page.
     */
    public function uploadScreen()
    {
        $portalsCount = ChargebackPortal::count();
        $recordsCount = ChargebackControl::count();

        return view('chargeback.upload', compact('portalsCount', 'recordsCount'));
    }

    /**
     * Download a blank / sample CSV template matching the exact schema.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'chargeback_template_' . date('Y-m-d') . '.csv';

        $sampleRow = [
            'OMT - ALERT',
            'Jan 02, 2026',
            'Jan--26',
            'Dec 20, 2025',
            'Dec--25',
            'Jan 15, 2026',
            'Jan 03, 2026',
            'ALERT',
            'New',
            'CHARGEBACK',
            'Chargeback received',
            'JQGNZ9',
            'Gilbert',
            'USD',
            '$524.00',
            '524.00',
            'CASE-' . strtoupper(substr(md5(uniqid()), 0, 12)),
            'VISA',
            '414720******0393',
            '10.4',
            'Fraudulent transaction reported by cardholder',
            'FLIGHT',
            'Customer dispute received from bank portal',
            'John Doe',
            'Air Ticket',
            '09:30',
            'Jan--26',
            'Jan--26',
            '1',
        ];

        return response()->streamDownload(function () use ($sampleRow) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->canonicalHeaders);
            fputcsv($handle, $sampleRow);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Process CSV Upload with Case Number deduplication and historical data import.
     * Note: Agent alert emails are STRICTLY bypassed during bulk upload.
     */
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:51200', // 50MB max
            'duplicate_action' => 'required|in:skip,update',
            'auto_link_bookings' => 'nullable|boolean',
        ]);

        $file = $request->file('csv_file');
        $duplicateAction = $request->input('duplicate_action', 'skip');
        $autoLinkBookings = $request->boolean('auto_link_bookings', true);

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            return back()->with('error', 'Unable to open the uploaded CSV file.');
        }

        // Read and clean UTF-8 BOM if present
        $headerLine = fgets($handle);
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
        $headers = str_getcsv($headerLine);

        if (!$headers || count($headers) < 5) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV format or missing header line.');
        }

        // Map column names to zero-based indexes
        $colIndex = [];
        foreach ($headers as $idx => $headerName) {
            $cleaned = strtolower(trim(str_replace(['"', "'"], '', $headerName)));
            $colIndex[$cleaned] = $idx;
        }

        // Identify key columns flexibly
        $caseNumIdx = $this->findColIndex($colIndex, ['case number', 'casenumber', 'case_number', 'case no']);
        if ($caseNumIdx === null) {
            fclose($handle);
            return back()->with('error', 'Required column "Case Number" was not found in the CSV header.');
        }

        $portalIdx       = $this->findColIndex($colIndex, ['portal']);
        $recDateIdx      = $this->findColIndex($colIndex, ['received date', 'received_date']);
        $recMonthIdx     = $this->findColIndex($colIndex, ['received month', 'received_month']);
        $bookDateIdx     = $this->findColIndex($colIndex, ['booking date', 'booking_date']);
        $bookMonthIdx    = $this->findColIndex($colIndex, ['booking month', 'booking_month']);
        $deadlineIdx     = $this->findColIndex($colIndex, ['deadline date', 'deadline_date']);
        $actionDateIdx   = $this->findColIndex($colIndex, ['action taken date', 'action_taken_date']);
        $cbkStatusIdx    = $this->findColIndex($colIndex, ['cbk status', 'cbk_status']);
        $caseTypeIdx     = $this->findColIndex($colIndex, ['case', 'case type', 'case_type']);
        $disputeTypeIdx  = $this->findColIndex($colIndex, ['dispute type', 'dispute_type']);
        $currStatusIdx   = $this->findColIndex($colIndex, ['current status', 'current_status', 'status']);
        $pnrIdx          = $this->findColIndex($colIndex, ['pnr', 'airline pnr', 'airline_pnr']);
        $agentIdx        = $this->findColIndex($colIndex, ['agentname', 'agent name', 'agent_name']);
        $currencyIdx     = $this->findColIndex($colIndex, ['currency']);
        $totalAmtIdx     = $this->findColIndex($colIndex, ['total booking amount', 'total_amount', 'total booking amt']);
        $disputedAmtIdx  = $this->findColIndex($colIndex, ['disputed amount ($)', 'disputed amount', 'disputed_amount']);
        $ccBrandIdx      = $this->findColIndex($colIndex, ['cc brand', 'cc_brand', 'card type']);
        $cardNoIdx       = $this->findColIndex($colIndex, ['card no.', 'card no', 'card_no', 'card number']);
        $reasonCodeIdx   = $this->findColIndex($colIndex, ['reason code', 'reason_code']);
        $reasonDescIdx   = $this->findColIndex($colIndex, ['reason code/description for charge back', 'reason description', 'reason_description']);
        $verticalIdx     = $this->findColIndex($colIndex, ['verticle', 'vertical']);
        $remarksIdx      = $this->findColIndex($colIndex, ['remarks', 'remark']);
        $passengerIdx    = $this->findColIndex($colIndex, ['passenger', 'passenger name', 'passenger ']);
        $serviceIdx      = $this->findColIndex($colIndex, ['service provided', 'service_provided']);
        $shiftTimeIdx    = $this->findColIndex($colIndex, ['shift time', 'shift time ']);
        $shiftMonthIdx   = $this->findColIndex($colIndex, ['shift month', 'shift_month']);
        $statementIdx    = $this->findColIndex($colIndex, ['statemen month', 'statemen month ', 'statement month']);
        $sdsIdx          = $this->findColIndex($colIndex, ['sds']);

        // Metrics counters
        $totalRowsRead       = 0;
        $createdRecords      = 0;
        $updatedRecords      = 0;
        $skippedDuplicates   = 0;
        $skippedEmptyRows    = 0;
        $newPortalsAdded     = 0;

        // In-file duplicate tracking
        $seenInFile = [];

        // Cache existing portals
        $knownPortals = ChargebackPortal::pluck('name')->map(fn($p) => strtoupper(trim($p)))->flip()->toArray();

        $userId = auth()->id();

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row, fn($val) => trim((string)$val) !== ''))) {
                $skippedEmptyRows++;
                continue;
            }

            $totalRowsRead++;

            $caseNumber = trim($row[$caseNumIdx] ?? '');
            if ($caseNumber === '') {
                // If Case Number is missing, skip row as invalid
                $skippedEmptyRows++;
                continue;
            }

            // Deduplication within the CSV file itself
            if (isset($seenInFile[$caseNumber])) {
                $skippedDuplicates++;
                if ($duplicateAction === 'skip') {
                    continue;
                }
            }
            $seenInFile[$caseNumber] = true;

            // Extract & sanitize data fields
            $portal = trim($row[$portalIdx ?? 0] ?? '') ?: 'OMT - ALERT';
            $caseTypeVal = strtolower(trim($row[$caseTypeIdx ?? 8] ?? 'new'));
            $caseType = in_array($caseTypeVal, ['new', 'old']) ? $caseTypeVal : 'new';

            $disputeTypeVal = trim($row[$disputeTypeIdx ?? 9] ?? '') ?: 'CHARGEBACK';
            $currentStatusVal = trim($row[$currStatusIdx ?? 10] ?? '') ?: 'Chargeback received';

            $recDateStr = trim($row[$recDateIdx ?? 1] ?? '');
            $receivedDate = $this->parseDate($recDateStr) ?: date('Y-m-d');
            $receivedMonth = trim($row[$recMonthIdx ?? 2] ?? '') ?: date('Y-m', strtotime($receivedDate));

            $bookDateStr = trim($row[$bookDateIdx ?? 3] ?? '');
            $bookingDate = $this->parseDate($bookDateStr);
            $bookingMonth = trim($row[$bookMonthIdx ?? 4] ?? '') ?: ($bookingDate ? date('Y-m', strtotime($bookingDate)) : null);

            $deadlineDateStr = trim($row[$deadlineIdx ?? 5] ?? '');
            $deadlineDate = $this->parseDate($deadlineDateStr);

            $actionDateStr = trim($row[$actionDateIdx ?? 6] ?? '');
            $actionTakenDate = $this->parseDate($actionDateStr);

            $cbkStatus = trim($row[$cbkStatusIdx ?? 7] ?? '') ?: null;
            $pnr = trim($row[$pnrIdx ?? 11] ?? '');
            $agentName = trim($row[$agentIdx ?? 12] ?? '') ?: null;
            $currency = strtoupper(trim($row[$currencyIdx ?? 13] ?? 'USD')) ?: 'USD';

            $totalAmt = $this->cleanAmount($row[$totalAmtIdx ?? 14] ?? 0);
            $disputedAmt = $this->cleanAmount($row[$disputedAmtIdx ?? 15] ?? 0);

            $ccBrand = trim($row[$ccBrandIdx ?? 17] ?? '') ?: null;
            $cardNo = trim($row[$cardNoIdx ?? 18] ?? '') ?: null;
            $reasonCode = trim($row[$reasonCodeIdx ?? 19] ?? '') ?: null;
            if (strtolower((string)$reasonCode) === 'null') {
                $reasonCode = null;
            }

            $reasonDesc = trim($row[$reasonDescIdx ?? 20] ?? '') ?: null;
            $vertical = trim($row[$verticalIdx ?? 21] ?? '') ?: 'FLIGHT';
            $remarks = trim($row[$remarksIdx ?? 22] ?? '') ?: null;
            $passenger = trim($row[$passengerIdx ?? 23] ?? '') ?: null;
            $serviceProvided = trim($row[$serviceIdx ?? 24] ?? '') ?: null;

            $shiftTime = trim($row[$shiftTimeIdx ?? 25] ?? '');
            if ($shiftTime === '--' || $shiftTime === 'Null' || $shiftTime === '') {
                $shiftTime = null;
            } else {
                $parsedTime = strtotime($shiftTime);
                $shiftTime = $parsedTime ? date('H:i:s', $parsedTime) : null;
            }

            $shiftMonth = trim($row[$shiftMonthIdx ?? 26] ?? '');
            if ($shiftMonth === '--' || $shiftMonth === '') $shiftMonth = null;

            $statementMonth = trim($row[$statementIdx ?? 27] ?? '');
            if ($statementMonth === '--' || $statementMonth === '') $statementMonth = null;

            $sdsVal = intval(preg_replace('/[^0-9]/', '', (string)($row[$sdsIdx ?? 28] ?? 1)));
            $sds = $sdsVal > 0 ? $sdsVal : 1;

            // Register new portal if not yet known
            $portalUpper = strtoupper($portal);
            if (!isset($knownPortals[$portalUpper]) && !empty($portal)) {
                ChargebackPortal::firstOrCreate(['name' => $portal]);
                $knownPortals[$portalUpper] = true;
                $newPortalsAdded++;
            }

            // Attempt PNR linking with CRM booking
            $bookingId = null;
            $bookingRef = null;
            if ($autoLinkBookings && !empty($pnr) && strtolower($pnr) !== 'nomatchfound') {
                $linkedBooking = Booking::where('airline_pnr', $pnr)
                    ->orWhere('gk_pnr', $pnr)
                    ->orWhere('booking_id', $pnr)
                    ->first();

                if ($linkedBooking) {
                    $bookingId = $linkedBooking->id;
                    $bookingRef = $linkedBooking->booking_id;
                    if (!$agentName && $linkedBooking->agent) {
                        $agentName = $linkedBooking->agent->alias_name ?: $linkedBooking->agent->name;
                    }
                    if ($totalAmt <= 0 && $linkedBooking->total_amount > 0) {
                        $totalAmt = (float)$linkedBooking->total_amount;
                    }
                }
            }

            $data = [
                'booking_id'           => $bookingId,
                'booking_reference'    => $bookingRef,
                'portal'               => $portal,
                'case_type'            => $caseType,
                'dispute_type'         => $disputeTypeVal,
                'received_date'        => $receivedDate,
                'received_month'       => $receivedMonth,
                'booking_date'         => $bookingDate,
                'booking_month'        => $bookingMonth,
                'deadline_date'        => $deadlineDate,
                'action_taken_date'    => $actionTakenDate,
                'cbk_status'           => $cbkStatus,
                'current_status'       => $currentStatusVal,
                'pnr'                  => $pnr,
                'agent_name'           => $agentName,
                'currency'             => $currency,
                'total_booking_amount' => $totalAmt,
                'disputed_amount'      => $disputedAmt,
                'cc_brand'             => $ccBrand,
                'card_no'              => $cardNo,
                'reason_code'          => $reasonCode,
                'reason_description'   => $reasonDesc,
                'vertical'             => $vertical,
                'remarks'              => $remarks,
                'passenger'            => $passenger,
                'service_provided'     => $serviceProvided,
                'shift_time'           => $shiftTime,
                'shift_month'          => $shiftMonth,
                'statement_month'      => $statementMonth,
                'sds'                  => $sds,
                'created_by'           => $userId,
            ];

            // Check database for duplicate Case Number
            $existing = ChargebackControl::where('case_number', $caseNumber)->first();

            if ($existing) {
                if ($duplicateAction === 'skip') {
                    $skippedDuplicates++;
                    continue;
                } else {
                    // Update existing record
                    $existing->update($data);
                    $updatedRecords++;
                }
            } else {
                // Insert new record
                $data['case_number'] = $caseNumber;
                ChargebackControl::create($data);
                $createdRecords++;
            }
        }

        fclose($handle);

        $msg = "CSV Processed Successfully! Total rows read: {$totalRowsRead}. New cases created: {$createdRecords}.";
        if ($updatedRecords > 0) {
            $msg .= " Existing cases updated: {$updatedRecords}.";
        }
        if ($skippedDuplicates > 0) {
            $msg .= " Duplicates filtered: {$skippedDuplicates}.";
        }
        if ($newPortalsAdded > 0) {
            $msg .= " New portals registered: {$newPortalsAdded}.";
        }
        $msg .= " (Note: Agent alert emails were suppressed for historical data import).";

        return redirect()->route('chargeback.index')->with('success', $msg);
    }

    /**
     * Export Chargeback records as CSV with date range, sorting, and selective row download.
     * Guaranteed to match the exact reference spreadsheet format.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = ChargebackControl::with(['booking.passengers', 'booking.agent']);

        // 1. Filter by specific Selected IDs (from table checkboxes)
        if ($request->filled('selected_ids')) {
            $ids = is_array($request->selected_ids)
                ? $request->selected_ids
                : explode(',', $request->selected_ids);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
        } else {
            // 2. Date Range Filter
            $dateField = in_array($request->date_field, ['booking_date', 'created_at'])
                ? $request->date_field
                : 'received_date';

            if ($request->filled('date_from')) {
                $query->whereDate($dateField, '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate($dateField, '<=', $request->date_to);
            }

            // 3. Status and Portal Filters
            if ($request->filled('portal')) {
                $query->where('portal', $request->portal);
            }
            if ($request->filled('dispute_type')) {
                $query->where('dispute_type', $request->dispute_type);
            }
            if ($request->filled('current_status')) {
                $query->where('current_status', $request->current_status);
            }
            if ($request->filled('case_type')) {
                $query->where('case_type', $request->case_type);
            }

            // 4. Search Filter
            if ($request->filled('search')) {
                $s = trim($request->search);
                $query->where(function ($q) use ($s) {
                    $q->where('case_number', 'like', "%{$s}%")
                      ->orWhere('pnr', 'like', "%{$s}%")
                      ->orWhere('agent_name', 'like', "%{$s}%")
                      ->orWhere('portal', 'like', "%{$s}%")
                      ->orWhere('dispute_type', 'like', "%{$s}%")
                      ->orWhere('current_status', 'like', "%{$s}%")
                      ->orWhere('booking_reference', 'like', "%{$s}%");
                });
            }
        }

        // Sorting
        $allowedSorts = ['id', 'received_date', 'booking_date', 'deadline_date', 'disputed_amount', 'portal', 'case_number'];
        $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'received_date';
        $sortDir = strtolower($request->sort_dir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $filename = 'chargeback_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write exact canonical header line
            fputcsv($handle, $this->canonicalHeaders);

            // Stream rows in chunks of 500
            $query->chunk(500, function ($records) use ($handle) {
                foreach ($records as $cb) {
                    $passengerStr = $cb->passenger;
                    if (!$passengerStr && $cb->booking && $cb->booking->passengers->isNotEmpty()) {
                        $passengerStr = $cb->booking->passengers->map(fn($p) => trim("{$p->first_name} {$p->last_name}"))->implode(', ');
                    }

                    $row = [
                        $cb->portal,
                        $cb->received_date ? $cb->received_date->format('M d, Y') : '',
                        $cb->received_month ?: ($cb->received_date ? $cb->received_date->format('M--y') : ''),
                        $cb->booking_date ? $cb->booking_date->format('M d, Y') : '',
                        $cb->booking_month ?: ($cb->booking_date ? $cb->booking_date->format('M--y') : ''),
                        $cb->deadline_date ? $cb->deadline_date->format('M d, Y') : '',
                        $cb->action_taken_date ? $cb->action_taken_date->format('M d, Y') : '',
                        $cb->cbk_status ?: '',
                        ucfirst($cb->case_type ?: 'New'),
                        $cb->dispute_type ?: '',
                        $cb->current_status ?: '',
                        $cb->pnr ?: '',
                        $cb->agent_name ?: '',
                        $cb->currency ?: 'USD',
                        '$' . number_format($cb->total_booking_amount, 2),
                        number_format($cb->disputed_amount, 2),
                        $cb->case_number,
                        $cb->cc_brand ?: '',
                        $cb->card_no ?: '',
                        $cb->reason_code ?: '',
                        $cb->reason_description ?: '',
                        $cb->vertical ?: 'FLIGHT',
                        $cb->remarks ?: '',
                        $passengerStr ?: '',
                        $cb->service_provided ?: '',
                        $cb->shift_time ?: '',
                        $cb->shift_month ?: '',
                        $cb->statement_month ?: '',
                        $cb->sds ?? 1,
                    ];

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Helper to find a column index by multiple candidate header names.
     */
    protected function findColIndex(array $colIndex, array $candidates): ?int
    {
        foreach ($candidates as $cand) {
            $key = strtolower(trim($cand));
            if (isset($colIndex[$key])) {
                return $colIndex[$key];
            }
        }
        return null;
    }

    /**
     * Helper to parse flexible date strings into Y-m-d.
     */
    protected function parseDate(?string $str): ?string
    {
        if (empty($str)) return null;
        $str = trim($str);
        if ($str === '--' || strtolower($str) === 'null') return null;

        try {
            $ts = strtotime($str);
            if ($ts !== false && $ts > 0) {
                return date('Y-m-d', $ts);
            }
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Helper to clean money strings ($1,975.80 -> 1975.80).
     */
    protected function cleanAmount(mixed $val): float
    {
        if (is_numeric($val)) return (float)$val;
        $cleaned = preg_replace('/[^0-9.]/', '', (string)$val);
        return is_numeric($cleaned) ? (float)$cleaned : 0.00;
    }
}
