<?php

namespace App\Services;

use App\Exceptions\PnrParseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PnrExpertService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.pnrexpert.key') ?? '';
        $url = config('services.pnrexpert.url') ?? 'https://www.pnrexpert.com/api/v1/pnr';
        if (!str_ends_with($url, '/pnr')) {
            $url = rtrim($url, '/') . '/pnr';
        }
        $this->apiUrl = $url;
    }

    /**
     * Parse PNR raw text using PNR Expert API.
     *
     * @param string $rawPnrText
     * @return array
     * @throws PnrParseException
     */
    public function parsePnr(string $rawPnrText): array
    {
        if (empty($this->apiKey)) {
            Log::error('PnrExpert API key is missing.');
            throw new PnrParseException('PnrExpert API configuration error: Key is missing.');
        }

        try {
            $verifySsl = (bool) config('services.pnrexpert.verify_ssl', false);

            $response = Http::withOptions([
                'verify' => $verifySsl,
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post($this->apiUrl, [
                'pnr' => $rawPnrText,
            ]);

            if ($response->failed()) {
                Log::error('PnrExpert API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new PnrParseException('PNR Parsing service error: ' . $response->status() . ' - ' . $response->body());
            }

            return $response->json();
        } catch (PnrParseException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('PnrExpert API unexpected exception', ['message' => $e->getMessage()]);
            throw new PnrParseException('PNR Parsing service encountered an unexpected error: ' . $e->getMessage(), 0, $e);
        }
    }
}
