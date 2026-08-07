<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PnrExpertService;
use App\Exceptions\PnrParseException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PnrController extends Controller
{
    protected PnrExpertService $pnrService;

    public function __construct(PnrExpertService $pnrService)
    {
        $this->pnrService = $pnrService;
    }

    /**
     * Parse raw PNR and return normalized structured data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'raw_pnr' => 'required|string',
        ]);

        try {
            $rawResult = $this->pnrService->parsePnr($request->input('raw_pnr'));
            $result = $rawResult['data'] ?? $rawResult;

            $passengers = collect($result['passengers'] ?? [])->map(function($pax, $idx) {
                $rawName = $pax['name'] ?? '';
                $firstName = $pax['first_name'] ?? '';
                $lastName = $pax['last_name'] ?? '';
                $title = $pax['title'] ?? 'MR';

                if ($rawName && str_contains($rawName, '/')) {
                    $nameParts = explode('/', $rawName);
                    $lastName = $nameParts[0] ?? '';
                    $firstWithTitle = trim($nameParts[1] ?? '');
                    $firstParts = explode(' ', $firstWithTitle);
                    $firstName = $firstParts[0] ?? '';
                    if (isset($firstParts[1])) {
                        $title = $firstParts[1];
                    }
                }

                return [
                    'first_name' => $firstName ?: 'PAX',
                    'last_name' => $lastName ?: 'PASSENGER',
                    'title' => strtoupper($title),
                    'pax_index' => $pax['pax_index'] ?? ($idx + 1),
                ];
            })->toArray();

            $rawPnrText = $request->input('raw_pnr');
            $rawFlights = array_values($result['flights'] ?? []);
            $totalFlightsCount = count($rawFlights);

            // Pre-parse OPERATED BY lines from raw PNR text if present
            $operatedByList = [];
            if (!empty($rawPnrText)) {
                if (preg_match_all('/OPERATED\s+BY\s+([A-Z0-9\s\.\-]+)/i', $rawPnrText, $opMatches)) {
                    $operatedByList = array_map('trim', $opMatches[1]);
                }
            }

            $flights = collect($rawFlights)->map(function($flight, $idx) use ($rawFlights, $totalFlightsCount, $operatedByList) {
                $dept = $flight['departingFrom'] ?? [];
                $arr = $flight['arrivingAt'] ?? [];

                // Extract Operated By details
                $operatedBy = $flight['operated_by']
                    ?? $flight['operatedBy']
                    ?? $flight['operatingAirline']
                    ?? $flight['operating_airline']
                    ?? $flight['operatingCarrierName']
                    ?? $flight['operating_carrier_name']
                    ?? null;

                if (is_array($operatedBy)) {
                    $operatedBy = $operatedBy['name'] ?? $operatedBy['code'] ?? null;
                }

                if (!$operatedBy && isset($operatedByList[$idx])) {
                    $operatedBy = $operatedByList[$idx];
                }

                $operatedByLogo = $flight['operated_by_logo'] ?? $flight['operatedByLogo'] ?? null;

                if (!$operatedByLogo && $operatedBy) {
                    $codeMatch = null;
                    $cleanOp = strtoupper(trim($operatedBy));
                    if (strlen($cleanOp) === 2) {
                        $codeMatch = $cleanOp;
                    } else {
                        $nameMap = [
                            'INDIGO' => '6E',
                            'AIR INDIA' => 'AI',
                            'SPICEJET' => 'SG',
                            'VISTARA' => 'UK',
                            'AKASA' => 'QP',
                            'AMERICAN' => 'AA',
                            'UNITED' => 'UA',
                            'DELTA' => 'DL',
                            'AIR FRANCE' => 'AF',
                            'BRITISH AIRWAYS' => 'BA',
                            'LUFTHANSA' => 'LH',
                            'EMIRATES' => 'EK',
                            'ETIHAD' => 'EY',
                            'QATAR' => 'QR',
                            'KLM' => 'KL',
                            'CATHAY' => 'CX',
                            'SINGAPORE' => 'SQ',
                        ];
                        foreach ($nameMap as $key => $val) {
                            if (str_contains($cleanOp, $key)) {
                                $codeMatch = $val;
                                break;
                            }
                        }
                    }
                    if ($codeMatch) {
                        $operatedByLogo = "https://pics.avs.io/200/50/{$codeMatch}.png";
                    }
                }

                // Flight duration formatting
                $durationStr = null;
                if (isset($flight['flightDuration']) && is_array($flight['flightDuration'])) {
                    $hrs = $flight['flightDuration']['hours'] ?? 0;
                    $mins = $flight['flightDuration']['minutes'] ?? 0;
                    $durationStr = ($hrs > 0 ? "{$hrs}h " : '') . "{$mins}m";
                } elseif (is_string($flight['flight_duration'] ?? null)) {
                    $durationStr = $flight['flight_duration'];
                }

                // Aircraft type formatting
                $aircraftTypeStr = null;
                if (isset($flight['aircraftType'])) {
                    if (is_array($flight['aircraftType'])) {
                        $aircraftTypeStr = $flight['aircraftType']['name'] ?? $flight['aircraftType']['code'] ?? null;
                    } else {
                        $aircraftTypeStr = (string)$flight['aircraftType'];
                    }
                } elseif (isset($flight['aircraft_type'])) {
                    if (is_array($flight['aircraft_type'])) {
                        $aircraftTypeStr = $flight['aircraft_type']['name'] ?? $flight['aircraft_type']['code'] ?? null;
                    } else {
                        $aircraftTypeStr = (string)$flight['aircraft_type'];
                    }
                }

                // Status text
                $statusStr = 'Confirmed';
                if (isset($flight['status'])) {
                    if (is_array($flight['status'])) {
                        $statusStr = $flight['status']['name'] ?? $flight['status']['code'] ?? 'Confirmed';
                    } else {
                        $statusStr = (string)$flight['status'];
                    }
                }

                // Transit/Layover calculation
                $transitText = null;
                if (isset($flight['transitTime']) && is_array($flight['transitTime'])) {
                    $tHrs = $flight['transitTime']['hours'] ?? 0;
                    $tMins = $flight['transitTime']['minutes'] ?? 0;
                    $destName = strtoupper($arr['cityName'] ?? $arr['airportName'] ?? $arr['airportCode'] ?? '');
                    if ($tHrs > 0 || $tMins > 0) {
                        $transitText = "{$tHrs}h {$tMins}m TRANSIT AT {$destName}";
                    }
                }

                // Fallback transit calculation if next flight exists
                if (!$transitText && $idx < $totalFlightsCount - 1) {
                    $nextFlight = $rawFlights[$idx + 1];
                    $currentArrTime = $arr['time'] ?? $flight['arrival_time'] ?? null;
                    $nextDeptTime = $nextFlight['departingFrom']['time'] ?? $nextFlight['departure_time'] ?? null;

                    if ($currentArrTime && $nextDeptTime) {
                        try {
                            $arrCarbon = Carbon::parse($currentArrTime);
                            $deptCarbon = Carbon::parse($nextDeptTime);
                            $diffMins = $arrCarbon->diffInMinutes($deptCarbon);
                            // Only display transit banner if connection is within 24 hours (1440 mins)
                            if ($diffMins > 0 && $diffMins <= 1440) {
                                $lHrs = floor($diffMins / 60);
                                $lMins = $diffMins % 60;
                                $destName = strtoupper($arr['cityName'] ?? $arr['airportName'] ?? $arr['airportCode'] ?? ($flight['destination'] ?? ''));
                                $transitText = "{$lHrs}h {$lMins}m TRANSIT AT {$destName}";
                            }
                        } catch (\Exception $e) {
                            // ignore parse error
                        }
                    }
                }

                return [
                    'flight_number' => (string)($flight['flight_number'] ?? ($flight['flightNumber'] ?? '')),
                    'operating_carrier' => $flight['operating_carrier'] ?? ($flight['iataCode'] ?? null),
                    'airline_name' => $flight['airline_name'] ?? ($flight['airlineName'] ?? null),
                    'airline_logo' => $flight['airline_logo'] ?? ($flight['airlineLogo'] ?? null),
                    'operated_by' => $operatedBy,
                    'operated_by_logo' => $operatedByLogo,
                    'origin' => $flight['origin'] ?? ($dept['airportCode'] ?? ''),
                    'origin_airport' => $flight['origin_airport'] ?? $flight['origin'] ?? ($dept['airportCode'] ?? ''),
                    'origin_city' => $flight['origin_city'] ?? ($dept['cityName'] ?? null),
                    'origin_airport_name' => $flight['origin_airport_name'] ?? ($dept['airportName'] ?? null),
                    'destination' => $flight['destination'] ?? ($arr['airportCode'] ?? ''),
                    'destination_airport' => $flight['destination_airport'] ?? $flight['destination'] ?? ($arr['airportCode'] ?? ''),
                    'destination_city' => $flight['destination_city'] ?? ($arr['cityName'] ?? null),
                    'destination_airport_name' => $flight['destination_airport_name'] ?? ($arr['airportName'] ?? null),
                    'departure_time' => $flight['departure_time'] ?? ($dept['time'] ?? null),
                    'arrival_time' => $flight['arrival_time'] ?? ($arr['time'] ?? null),
                    'day_offset' => (int)($arr['dayOffset'] ?? $flight['day_offset'] ?? 0),
                    'cabin' => $flight['cabin'] ?? null,
                    'booking_class' => $flight['booking_class'] ?? ($flight['bookingClass'] ?? null),
                    'aircraft_type' => $aircraftTypeStr,
                    'status' => $statusStr,
                    'flight_duration' => $durationStr,
                    'transit_text' => $transitText,
                ];
            })->toArray();

            $firstFlight = $flights[0] ?? [];
            $lastFlight = end($flights) ?: [];

            // Calculate total trip duration if possible
            $totalTripDuration = null;
            if (!empty($firstFlight['departure_time']) && !empty($lastFlight['arrival_time'])) {
                try {
                    $startCarbon = Carbon::parse($firstFlight['departure_time']);
                    $endCarbon = Carbon::parse($lastFlight['arrival_time']);
                    $diffMins = $startCarbon->diffInMinutes($endCarbon);
                    if ($diffMins > 0) {
                        $tHrs = floor($diffMins / 60);
                        $tMins = $diffMins % 60;
                        $totalTripDuration = "{$tHrs}h {$tMins}m";
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }

            // Determine trip type
            $detectedTripType = 'one_way';
            if (isset($result['trip_type'])) {
                $rawTripType = strtolower((string)$result['trip_type']);
                if (str_contains($rawTripType, 'round')) {
                    $detectedTripType = 'round_trip';
                } elseif (str_contains($rawTripType, 'multi')) {
                    $detectedTripType = 'multi_city';
                }
            } elseif (isset($result['tripType'])) {
                $rawTripType = strtolower((string)$result['tripType']);
                if (str_contains($rawTripType, 'round')) {
                    $detectedTripType = 'round_trip';
                } elseif (str_contains($rawTripType, 'multi')) {
                    $detectedTripType = 'multi_city';
                }
            } else {
                $flightCount = count($flights);
                if ($flightCount == 1) {
                    $detectedTripType = 'one_way';
                } elseif ($flightCount >= 2) {
                    $firstOrigin = strtoupper($firstFlight['origin'] ?? $firstFlight['origin_airport'] ?? '');
                    $lastDest = strtoupper($lastFlight['destination'] ?? $lastFlight['destination_airport'] ?? '');
                    if ($firstOrigin && $lastDest && $firstOrigin === $lastDest) {
                        $detectedTripType = 'round_trip';
                    } else {
                        $detectedTripType = 'multi_city';
                    }
                }
            }

            // Extract airline PNR locator from any matching key
            $airlinePnr = $result['airline_pnr'] 
                ?? $result['airlineLocator'] 
                ?? $result['recordLocator'] 
                ?? $result['pnrCode']
                ?? $result['locator'] 
                ?? $result['pnr'] 
                ?? null;

            $normalized = [
                'airline_code' => $result['airline_code'] ?? ($firstFlight['operating_carrier'] ?? null),
                'airline_name' => $result['airline_name'] ?? ($firstFlight['airline_name'] ?? null),
                'airline_pnr' => $airlinePnr,
                'trip_type' => $detectedTripType,
                'origin_airport' => $result['origin_airport'] ?? ($firstFlight['origin'] ?? null),
                'origin_city' => $firstFlight['origin_city'] ?? null,
                'destination_airport' => $result['destination_airport'] ?? ($lastFlight['destination'] ?? null),
                'destination_city' => $lastFlight['destination_city'] ?? null,
                'travel_date' => $result['travel_date'] ?? ($firstFlight['departure_time'] ?? null),
                'total_trip_duration' => $totalTripDuration,
                'passengers' => $passengers,
                'flights' => $flights,
            ];

            if (array_key_exists('tickets', $result)) {
                $normalized['tickets'] = $result['tickets'];
            }
            if (array_key_exists('seats', $result)) {
                $normalized['seats'] = $result['seats'];
            }

            return response()->json([
                'success' => true,
                'data' => $normalized,
            ]);
        } catch (PnrParseException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
