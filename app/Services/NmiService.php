<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\NmiTransaction;
use Illuminate\Support\Facades\Http;
use Exception;

class NmiService
{
    protected string $securityKey;
    protected string $apiUrl;
    protected string $tokenizationKey;
    protected ?int $merchantId = null;

    public function __construct()
    {
        $this->securityKey = (string) config('nmi.security_key');
        $this->apiUrl = (string) config('nmi.api_url');
        $this->tokenizationKey = (string) config('nmi.tokenization_key');
    }

    public function useMerchant(?Merchant $merchant): static
    {
        if ($merchant) {
            $securityKey = $merchant->security_key ?: config('nmi.security_key');
            $apiUrl = $merchant->api_url ?: config('nmi.api_url');
            $tokenizationKey = $merchant->tokenization_key ?: config('nmi.tokenization_key');

            if (empty($securityKey)) {
                throw new Exception('No security key found for selected merchant and no fallback NMI key is configured.');
            }

            if (empty($apiUrl)) {
                throw new Exception('No API URL found for selected merchant and no fallback NMI URL is configured.');
            }

            $this->securityKey = (string) $securityKey;
            $this->apiUrl = (string) $apiUrl;
            $this->tokenizationKey = (string) $tokenizationKey;
            $this->merchantId = $merchant->id;
        }

        return $this;
    }

    public function getSecurityKey(): string
    {
        return $this->securityKey;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getTokenizationKey(): string
    {
        return $this->tokenizationKey;
    }

    public function getMerchantId(): ?int
    {
        return $this->merchantId;
    }

    /**
     * Raw-card sale flow.
     */
    public function sale(array $data): array
    {
        $cleanCardNumber = isset($data['ccnumber']) ? preg_replace('/\D/', '', (string) $data['ccnumber']) : '';
        $cleanExp = $this->normalizeExpiration($data['ccexp'] ?? '');

        $payload = [
            'security_key' => $this->securityKey,
            'type'         => 'sale',
            'amount'       => number_format((float) $data['amount'], 2, '.', ''),
            'ccnumber'     => $cleanCardNumber,
            'ccexp'        => $cleanExp,
            'cvv'          => isset($data['cvv']) ? preg_replace('/\D/', '', (string) $data['cvv']) : '',
            'first_name'   => $data['first_name'] ?? null,
            'last_name'    => $data['last_name'] ?? null,
            'address1'     => $data['address1'] ?? null,
            'city'         => $data['city'] ?? null,
            'state'        => $data['state'] ?? null,
            'zip'          => $data['zip'] ?? null,
            'country'      => $data['country'] ?? null,
            'email'        => $data['email'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'orderid'      => $data['order_id'] ?? null,
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Normalize credit card expiration date to MMYY format.
     */
    public function normalizeExpiration(string $exp): string
    {
        $digits = preg_replace('/\D/', '', $exp);
        if (strlen($digits) === 4) {
            return $digits; // MMYY
        }
        if (strlen($digits) === 6) {
            // MMYYYY -> MMYY
            return substr($digits, 0, 2) . substr($digits, 4, 2);
        }
        return $digits;
    }

    /**
     * Token-based sale flow for Collect.js / tokenized usage.
     */
    public function saleWithToken(array $data): array
    {
        $payload = [
            'security_key'  => $this->securityKey,
            'type'          => 'sale',
            'amount'        => number_format((float) $data['amount'], 2, '.', ''),
            'payment_token' => $data['payment_token'],
            'first_name'    => $data['first_name'] ?? null,
            'last_name'     => $data['last_name'] ?? null,
            'address1'      => $data['address1'] ?? null,
            'city'          => $data['city'] ?? null,
            'state'         => $data['state'] ?? null,
            'zip'           => $data['zip'] ?? null,
            'country'       => $data['country'] ?? null,
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'orderid'       => $data['order_id'] ?? null,
        ];

        return $this->sendRequest($payload);
    }

    protected function sendRequest(array $payload): array
    {
        $response = Http::asForm()->post($this->apiUrl, $payload);

        $result = [];
        parse_str($response->body(), $result);

        return $result;
    }

    public function logTransaction(
        array $requestData,
        array $response,
        ?int $merchantId = null,
        ?int $bookingId = null,
        ?int $paymentLinkId = null
    ): NmiTransaction {
        $status = $this->mapStatus($response);

        $cardLast4 = isset($requestData['ccnumber'])
            ? substr(preg_replace('/\D/', '', (string) $requestData['ccnumber']), -4)
            : ($response['last4'] ?? null);

        return NmiTransaction::create([
            'merchant_id'         => $merchantId ?? $this->merchantId,
            'booking_id'          => $bookingId ?? ($requestData['booking_id'] ?? null),
            'payment_link_id'     => $paymentLinkId ?? ($requestData['payment_link_id'] ?? null),
            'order_id'            => $requestData['order_id'] ?? null,
            'transaction_id'      => $response['transactionid'] ?? null,
            'type'                => $response['type'] ?? 'sale',
            'customer_first_name' => $requestData['first_name'] ?? null,
            'customer_last_name'  => $requestData['last_name'] ?? null,
            'email'               => $requestData['email'] ?? null,
            'card_last4'          => $cardLast4,
            'card_brand'          => $response['cardbrand'] ?? null,
            'address1'            => $requestData['address1'] ?? null,
            'city'                => $requestData['city'] ?? null,
            'state'               => $requestData['state'] ?? null,
            'zip'                 => $requestData['zip'] ?? null,
            'country'             => $requestData['country'] ?? null,
            'amount'              => $requestData['amount'] ?? 0,
            'currency'            => $requestData['currency'] ?? 'USD',
            'status'              => $status,
            'processed_at'        => now(),
            'raw_response'        => $response,
        ]);
    }

    public function mapStatus(array $response): string
    {
        if (! isset($response['response'])) {
            return 'error';
        }

        return match ((string) $response['response']) {
            '1'     => 'approved',
            '2'     => 'declined',
            default => 'error',
        };
    }

    /**
     * Comprehensive interpretation of NMI response.
     */
    public function interpretResponse(array $response): array
    {
        $status = $this->mapStatus($response);
        $responseText = $response['responsetext'] ?? 'No response message provided';
        $responseCode = $response['response_code'] ?? null;
        $authCode = $response['authcode'] ?? null;
        $transactionId = $response['transactionid'] ?? null;
        $avs = $response['avsresponse'] ?? null;
        $cvv = $response['cvvresponse'] ?? null;

        $friendlyMessage = match ($status) {
            'approved' => 'Transaction Approved! Authorization Code: ' . ($authCode ?: 'N/A'),
            'declined' => $this->getDeclineReason($responseCode, $responseText),
            default    => 'Transaction Error: ' . $responseText,
        };

        return [
            'is_approved'       => $status === 'approved',
            'status'            => $status,
            'response'          => $response['response'] ?? null,
            'response_code'     => $responseCode,
            'response_text'     => $responseText,
            'friendly_message'  => $friendlyMessage,
            'auth_code'         => $authCode,
            'transaction_id'    => $transactionId,
            'avs_code'          => $avs,
            'avs_description'   => $this->getAvsMessage($avs),
            'cvv_code'          => $cvv,
            'cvv_description'   => $this->getCvvMessage($cvv),
            'order_id'          => $response['orderid'] ?? null,
            'type'              => $response['type'] ?? 'sale',
        ];
    }

    public function getDeclineReason(?string $code, string $default): string
    {
        return match ((string) $code) {
            '200' => 'Declined: General decline by card issuer or processor.',
            '201' => 'Declined: Do Not Honor. Card issuer declined the transaction.',
            '202' => 'Declined: Insufficient Funds in cardholder account.',
            '203' => 'Declined: Credit limit exceeded on account.',
            '204' => 'Declined: Card has expired.',
            '220' => 'Declined: Incorrect PIN or CVV security code.',
            '225' => 'Declined: Security violation / fraud risk flagged.',
            '250' => 'Declined: Pick up card / lost or stolen flag.',
            '300' => 'Declined: Gateway rejected (Check card number or key).',
            default => 'Declined: ' . $default,
        };
    }

    public function getAvsMessage(?string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        return match (strtoupper($code)) {
            'Y' => 'Full Match: Street address & 5-digit ZIP match.',
            'A' => 'Partial Match: Street address matches, ZIP does not.',
            'Z' => 'Partial Match: 5-digit ZIP matches, street address does not.',
            'N' => 'No Match: Neither address nor ZIP match.',
            'U' => 'Unavailable: Issuer does not support AVS verification.',
            'R' => 'Retry: System unavailable or timed out.',
            'E' => 'Invalid: AVS data is invalid or ineligible.',
            'S' => 'Unsupported: Service not supported by issuing bank.',
            default => 'AVS Response: ' . $code,
        };
    }

    public function getCvvMessage(?string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        return match (strtoupper($code)) {
            'M' => 'Match: CVV / CVC code matched successfully.',
            'N' => 'No Match: CVV code does not match card record.',
            'P' => 'Not Processed: CVV was not processed by issuer.',
            'S' => 'Suspicious / Not Present: CVV was flagged as not present.',
            'U' => 'Unavailable: Issuer is not certified for CVV verification.',
            default => 'CVV Response: ' . $code,
        };
    }
}
