<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Credit Card Authorization</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon/android-chrome-192x192.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(79,70,229,0.15),rgba(255,255,255,0))]">
    <div class="sm:mx-auto sm:w-full sm:max-w-2xl text-center">
        <!-- Logo -->
        <div class="inline-flex bg-indigo-600 p-3 rounded-2xl text-white font-bold shadow-lg shadow-indigo-500/20 mb-4">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h2 class="text-3xl font-bold tracking-tight text-white">Payment & Flight Authorization</h2>
        <p class="mt-2 text-sm text-slate-400">Secure authorization page for booking reference <span class="font-bold text-indigo-400 font-mono">#{{ $booking->booking_id }}</span></p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl px-4">
        @if (session('success'))
            <div class="mb-6 p-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-center shadow-xl">
                <div class="inline-flex p-3 rounded-full bg-emerald-500/20 text-emerald-400 mb-3">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Authorization Complete</h3>
                <p class="text-sm text-slate-300">{{ session('success') }}</p>
            </div>
        @else
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-8">
                <!-- Section 1: Billing Summary -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-slate-200 border-b border-slate-800 pb-2">Billing Summary</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm bg-slate-950/40 p-4 rounded-xl border border-slate-850">
                        <div>
                            <div class="text-xs text-slate-500 font-medium">Card Holder Name</div>
                            <div class="mt-1 font-semibold text-slate-200">{{ $booking->card_holder_name ?: 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 font-medium">Card Type &amp; Number</div>
                            <div class="mt-1 font-mono text-slate-200">{{ $booking->card_type ?: 'Card' }} (XXXX-XXXX-XXXX-{{ $booking->card_last_4 ?: 'XXXX' }})</div>
                        </div>
                        @if(!empty($booking->card_expiration))
                        <div>
                            <div class="text-xs text-slate-500 font-medium">Card Expiration</div>
                            <div class="mt-1 font-mono text-slate-200">{{ $booking->card_expiration }}</div>
                        </div>
                        @endif
                        <div class="col-span-2 pt-2 border-t border-slate-800/60 flex justify-between items-center">
                            <span class="text-slate-400 font-medium">Total Charge Amount</span>
                            <span class="text-xl font-bold text-indigo-400">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</span>
                        </div>
                    </div>

                    @if($booking->bookingCards && $booking->bookingCards->count() > 0)
                        <!-- Multiple Authorized Cards Roster -->
                        <div class="mt-4 pt-3 border-t border-slate-800 space-y-3">
                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Authorized Payment Cards ({{ $booking->bookingCards->count() + 1 }})</h4>
                            <div class="space-y-2">
                                <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 text-xs flex justify-between items-center">
                                    <div>
                                        <span class="font-semibold text-white">{{ $booking->card_holder_name ?: 'Primary Card' }}</span>
                                        <span class="text-slate-400 ms-2">({{ $booking->card_type ?: 'Card' }})</span>
                                    </div>
                                    <div class="font-mono text-indigo-300">XXXX-XXXX-XXXX-{{ $booking->card_last_4 }} @if($booking->card_expiration) | Exp: {{ $booking->card_expiration }} @endif</div>
                                </div>
                                @foreach($booking->bookingCards as $bCard)
                                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 text-xs flex justify-between items-center">
                                        <div>
                                            <span class="font-semibold text-white">{{ $bCard->card_holder_name ?: 'Additional Card' }}</span>
                                            <span class="text-slate-400 ms-2">({{ $bCard->card_type ?: 'Card' }})</span>
                                        </div>
                                        <div class="font-mono text-indigo-300">XXXX-XXXX-XXXX-{{ $bCard->card_last_4 }} @if($bCard->card_expiration) | Exp: {{ $bCard->card_expiration }} @endif</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Section 2: Passengers -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-slate-200 border-b border-slate-800 pb-2">Passengers</h3>
                    <div class="space-y-2">
                        @forelse($booking->passengers as $pax)
                            <div class="text-sm px-4 py-2.5 rounded-xl bg-slate-950/20 border border-slate-850 flex items-center justify-between">
                                <span class="font-medium text-slate-350">{{ $pax->title }} {{ $pax->first_name }} {{ $pax->last_name }}</span>
                                <span class="text-xs text-slate-550 font-mono">Pax index: {{ $pax->pax_index ?: 'N/A' }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">No passenger details recorded.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Section 3: Flights -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-slate-200 border-b border-slate-800 pb-2">Flight Details</h3>
                    <div class="space-y-3">
                        @php
                            $authShowFlights = ($booking->flightSegments && $booking->flightSegments->isNotEmpty())
                                ? $booking->flightSegments
                                : $booking->bookingFlights;
                        @endphp
                        @forelse($authShowFlights as $flight)
                            <div class="p-4 bg-slate-950/40 rounded-xl border border-slate-850 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
                                <div>
                                    <div class="text-sm font-semibold text-slate-200">
                                        {{ $flight->operating_carrier }} {{ $flight->flight_number }}
                                        @if(!empty($flight->operated_by))
                                            <span class="text-xs text-sky-400 font-bold ms-2">&#x2708;&#xFE0F; Operated by {{ $flight->operated_by }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">{{ $flight->origin_airport }} to {{ $flight->destination_airport }} | Cabin: {{ $flight->cabin ?: 'N/A' }}</div>
                                </div>
                                <div class="text-right sm:text-right">
                                    <div class="text-xs text-slate-300">{{ $flight->departure_time ? $flight->departure_time->format('M d, Y H:i') : 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Departure</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">No flights recorded for this booking.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Form Section -->
                <form action="{{ route('customer.authorize.approve', ['booking' => $booking->id, 'hash' => $hash]) }}" method="POST" class="space-y-6 pt-4 border-t border-slate-800">
                    @csrf
                    
                    <!-- Terms and conditions -->
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input id="terms_accepted" name="terms_accepted" type="checkbox" required class="h-4 w-4 rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500/40 focus:ring-offset-slate-900 transition duration-200">
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="terms_accepted" class="font-medium text-slate-300">Terms & Service Agreement *</label>
                            <p class="text-xs text-slate-500">I certify that the passenger names, itinerary segments, and charge amounts listed above are correct. I authorize the total charge amount to be billed to the credit card listed above.</p>
                        </div>
                    </div>

                    <!-- Digital Signature -->
                    <div>
                        <label for="signature" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Electronic Signature (Type Full Legal Name) *</label>
                        <input type="text" id="signature" name="signature" required placeholder="Jane Doe" class="block w-full rounded-xl bg-slate-950 border border-slate-800 px-4 py-2.5 text-sm text-slate-200 placeholder-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition duration-200">
                    </div>

                    @if ($errors->any())
                        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <button type="submit" class="w-full inline-flex justify-center items-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white transition duration-200 shadow-lg shadow-indigo-600/30">
                        Authorize Charge
                    </button>
                </form>
            </div>
        @endif
        
        <div class="text-center mt-6 text-xs text-slate-600 flex items-center justify-center space-x-1">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <span>SSL Secured Connection & Encrypted Sign-off</span>
        </div>
    </div>
</body>
</html>
