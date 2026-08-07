<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\BookingRemark;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Eloquent model creation, relationships, auto-generated booking_id,
     * and total_mco calculations.
     */
    public function test_models_and_relationships_work_correctly(): void
    {
        // 1. Create a User (Agent)
        $agent = User::create([
            'name' => 'John Doe',
            'alias_name' => 'JD Agent',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password123'),
            'contact' => '+1234567890',
            'extension' => '101',
            'role' => 'agent',
            'joining_date' => '2026-08-01',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'alias_name' => 'JD Agent',
        ]);

        // 2. Create a Booking associated with the agent
        $booking = Booking::create([
            'agent_id' => $agent->id,
            'booking_date' => '2026-08-03',
            'service_provided' => 'new_booking',
            'total_amount' => 500.00,
            'paid_to_airline' => 350.00,
            'total_mco' => 150.00,
        ]);

        // Verify booking_id is auto-generated, 7 characters, uppercase, and alphanumeric
        $this->assertNotEmpty($booking->booking_id);
        $this->assertEquals(7, strlen($booking->booking_id));
        $this->assertEquals(strtoupper($booking->booking_id), $booking->booking_id);
        $this->assertTrue(ctype_alnum($booking->booking_id));

        // Verify total_mco is saved as passed: 150.00
        $this->assertEquals(150.00, $booking->total_mco);

        // Verify database contains the generated values
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_id' => $booking->booking_id,
            'total_mco' => 150.00,
        ]);

        // 3. Create a Passenger
        $passenger = Passenger::create([
            'booking_id' => $booking->id,
            'pax_index' => 'P1',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'title' => 'MS',
        ]);

        $this->assertDatabaseHas('passengers', [
            'id' => $passenger->id,
            'first_name' => 'Alice',
        ]);

        // 4. Create a Booking Flight
        $flight = BookingFlight::create([
            'booking_id' => $booking->id,
            'flight_number' => 'AA123',
            'operating_carrier' => 'American Airlines',
            'origin_airport' => 'JFK',
            'destination_airport' => 'LHR',
            'departure_time' => '2026-08-10 14:00:00',
            'arrival_time' => '2026-08-11 02:00:00',
        ]);

        $this->assertDatabaseHas('booking_flights', [
            'id' => $flight->id,
            'flight_number' => 'AA123',
        ]);

        // 5. Create a Booking Remark
        $remark = BookingRemark::create([
            'booking_id' => $booking->id,
            'user_id' => $agent->id,
            'remark' => 'Passenger requested a window seat.',
            'type' => 'agent_remark',
        ]);

        $this->assertDatabaseHas('booking_remarks', [
            'id' => $remark->id,
            'remark' => 'Passenger requested a window seat.',
        ]);

        // 6. Test Relationships
        
        // User relationships
        $this->assertCount(1, $agent->bookings);
        $this->assertEquals($booking->id, $agent->bookings->first()->id);
        $this->assertCount(1, $agent->bookingRemarks);
        $this->assertEquals($remark->id, $agent->bookingRemarks->first()->id);

        // Booking relationships
        $this->assertEquals($agent->id, $booking->agent->id);
        $this->assertCount(1, $booking->passengers);
        $this->assertEquals($passenger->id, $booking->passengers->first()->id);
        $this->assertCount(1, $booking->bookingFlights);
        $this->assertEquals($flight->id, $booking->bookingFlights->first()->id);
        $this->assertCount(1, $booking->bookingRemarks);
        $this->assertEquals($remark->id, $booking->bookingRemarks->first()->id);

        // Passenger relationship
        $this->assertEquals($booking->id, $passenger->booking->id);

        // Flight relationship
        $this->assertEquals($booking->id, $flight->booking->id);

        // Remark relationships
        $this->assertEquals($booking->id, $remark->booking->id);
        $this->assertEquals($agent->id, $remark->user->id);
    }

    /**
     * Test total_mco accessor/mutator logic.
     */
    public function test_total_mco_accessor_mutator(): void
    {
        $agent = User::create([
            'name' => 'Jane Agent',
            'alias_name' => 'Jane A',
            'email' => 'jane.agent@example.com',
            'password' => bcrypt('password123'),
        ]);

        $booking = new Booking([
            'agent_id' => $agent->id,
            'booking_date' => '2026-08-03',
            'service_provided' => 'new_booking',
            'total_amount' => 1000.00,
            'paid_to_airline' => 700.00,
            'total_mco' => 300.00,
        ]);

        // Accessor should retrieve the stored value
        $this->assertEquals(300.00, $booking->total_mco);

        $booking->save();

        // After save, the accessor should still get the right value and database has it too
        $this->assertEquals(300.00, $booking->total_mco);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'total_mco' => 300.00,
        ]);

        // Modify value directly and verify update
        $booking->total_mco = 350.00;
        $booking->save();

        $this->assertEquals(350.00, $booking->total_mco);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'total_mco' => 350.00,
        ]);
    }
}
