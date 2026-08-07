<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.pnrexpert.key');
$url = 'https://www.pnrexpert.com/api/v1/pnr';

$samplePnr = "1.SMITH/JOHN MR\n2 AA 100 Y 15AUG DFWORD HK1 0800 1000";

$payloadKeys = [
    'pnr',
    'raw_pnr',
    'pnr_text',
    'text',
    'itinerary',
    'pnr_string',
    'data',
];

foreach ($payloadKeys as $key) {
    $response = Illuminate\Support\Facades\Http::withoutVerifying()
        ->withToken($apiKey)
        ->post($url, [
            $key => $samplePnr
        ]);
    
    echo "Testing payload key ['$key']: Status " . $response->status() . " Body: " . $response->body() . PHP_EOL;
}
