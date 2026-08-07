<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PnrParsingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set a dummy api key for testing
        config(['services.pnrexpert.key' => 'test-api-key']);
        config(['services.pnrexpert.url' => 'https://www.pnrexpert.com/api/v1/pnr']);
    }

    /**
     * Test raw_pnr input validation.
     */
    public function test_raw_pnr_is_required_to_parse(): void
    {
        $response = $this->postJson('/api/pnr/parse', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['raw_pnr']);
    }

    /**
     * Test successful parsing of raw PNR.
     */
    public function test_successful_pnr_parsing(): void
    {
        // Mock successful PNR Expert API response
        Http::fake([
            'https://www.pnrexpert.com/api/v1/pnr' => Http::response([
                'airline_code' => 'AA',
                'airline_name' => 'American Airlines',
                'airline_pnr' => 'XYZ123',
                'origin_airport' => 'JFK',
                'destination_airport' => 'LHR',
                'travel_date' => '2026-08-10',
                'passengers' => [
                    [
                        'first_name' => 'Alice',
                        'last_name' => 'Smith',
                        'title' => 'MS',
                        'pax_index' => 'P1'
                    ]
                ],
                'flights' => [
                    [
                        'flight_number' => 'AA123',
                        'operating_carrier' => 'American Airlines',
                        'origin' => 'JFK',
                        'destination' => 'LHR',
                        'departure_time' => '2026-08-10 14:00:00',
                        'arrival_time' => '2026-08-11 02:00:00',
                        'cabin' => 'Economy',
                        'booking_class' => 'Y',
                        'aircraft_type' => '777'
                    ]
                ],
                'tickets' => ['001-1234567890'],
                'seats' => ['12A']
            ], 200)
        ]);

        $response = $this->postJson('/api/pnr/parse', [
            'raw_pnr' => 'AMADEUS PNR TEXT SEGMENT'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'airline_code' => 'AA',
                    'airline_name' => 'American Airlines',
                    'airline_pnr' => 'XYZ123',
                    'origin_airport' => 'JFK',
                    'destination_airport' => 'LHR',
                    'travel_date' => '2026-08-10',
                    'passengers' => [
                        [
                            'first_name' => 'Alice',
                            'last_name' => 'Smith',
                            'title' => 'MS',
                            'pax_index' => 'P1'
                        ]
                    ],
                    'flights' => [
                        [
                            'flight_number' => 'AA123',
                            'operating_carrier' => 'American Airlines',
                            'origin' => 'JFK',
                            'destination' => 'LHR',
                            'departure_time' => '2026-08-10 14:00:00',
                            'arrival_time' => '2026-08-11 02:00:00',
                            'cabin' => 'Economy',
                            'booking_class' => 'Y',
                            'aircraft_type' => '777'
                        ]
                    ],
                    'tickets' => ['001-1234567890'],
                    'seats' => ['12A']
                ]
            ]);
    }

    /**
     * Test failed PNR Expert API request.
     */
    public function test_failed_pnr_parsing_api_call(): void
    {
        // Mock non-200 response from PNR Expert API
        Http::fake([
            'https://www.pnrexpert.com/api/v1/pnr' => Http::response('Unauthorized or invalid key', 401)
        ]);

        $response = $this->postJson('/api/pnr/parse', [
            'raw_pnr' => 'SOME RAW PNR'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'PNR Parsing service error: 401 - Unauthorized or invalid key'
            ]);
    }

    /**
     * Test exception when API configuration is missing.
     */
    public function test_missing_api_key_configuration(): void
    {
        // Clear API key config
        config(['services.pnrexpert.key' => null]);

        $response = $this->postJson('/api/pnr/parse', [
            'raw_pnr' => 'SOME RAW PNR'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'PnrExpert API configuration error: Key is missing.'
            ]);
    }
}
