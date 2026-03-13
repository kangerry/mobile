<?php

namespace App\Services;

use Doku\Snap\Commons\Config as SnapConfig;
use Doku\Snap\Commons\Helper as SnapHelper;
use Doku\Snap\Models\Payment\PaymentAdditionalInfoRequestDto;
use Doku\Snap\Models\Payment\PaymentRequestDto;
use Doku\Snap\Models\TotalAmount\TotalAmount;
use Doku\Snap\Services\DirectDebitServices;
use Doku\Snap\Services\TokenServices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DokuClient
{
    protected function resolveSettings(string $koperasiId): ?array
    {
        $kop = DB::table('koperasi')->where('id', (int) $koperasiId)->first();
        if (! $kop) {
            return null;
        }
        $row = DB::table('doku_settings')->where('kode_koperasi', $kop->kode_koperasi)->first();
        if (! $row) {
            return null;
        }

        $envName = $row->env === 'production' ? 'production' : 'sandbox';
        $base = trim((string) $row->base_url);
        if ($base === '') {
            $base = $envName === 'production' ? 'https://api.doku.com' : 'https://api-sandbox.doku.com';
        }

        return [
            'env_name' => $envName,
            'env' => $envName === 'production' ? 'true' : 'false',
            'client_id' => $row->client_id,
            'secret_key' => $row->secret_key,
            'api_key' => $row->api_key ?? '',
            'private_key' => $row->private_key ?? '',
            'public_key' => $row->public_key,
            'base_url' => rtrim($base, '/'),
            'allow_sandbox_simulation' => isset($row->allow_sandbox_simulation) ? (bool) $row->allow_sandbox_simulation : true,
        ];
    }

    public function isConfigured(string $koperasiId): bool
    {
        return (bool) $this->resolveSettings($koperasiId);
    }

    public function getEnvName(string $koperasiId): ?string
    {
        $cfg = $this->resolveSettings($koperasiId);

        return $cfg['env_name'] ?? null;
    }

    public function allowSandboxSimulation(string $koperasiId): bool
    {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return true;
        }

        return (bool) ($cfg['allow_sandbox_simulation'] ?? true);
    }

    protected function getTokenB2B(array $cfg): ?string
    {
        if (empty($cfg['private_key'])) {
            return null;
        }
        $svc = new TokenServices;
        $ts = $svc->getTimestamp();
        $sig = $svc->createSignature($cfg['private_key'], $cfg['client_id'], $ts);
        $req = $svc->createTokenB2BRequestDto($sig, $ts, $cfg['client_id']);
        $resp = $svc->createTokenB2B($req, $cfg['env']);

        return $resp->accessToken ?? null;
    }

    public function createCheckout(
        string $koperasiId,
        string $partnerReferenceNo,
        int $amount,
        string $successUrl,
        string $failedUrl
    ): ?array {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return null;
        }
        if (empty($cfg['private_key']) || empty($cfg['secret_key']) || empty($cfg['client_id'])) {
            return null;
        }
        $token = $this->getTokenB2B($cfg);
        if (! $token) {
            return null;
        }
        $total = new TotalAmount(number_format((float) $amount, 2, '.', ''), 'IDR');
        $add = new PaymentAdditionalInfoRequestDto(
            'CHECKOUT',
            null,
            $successUrl,
            $failedUrl,
            null,
            null
        );
        $req = new PaymentRequestDto(
            $partnerReferenceNo,
            $total,
            null,
            $add,
            null,
            null
        );
        $ts = new TokenServices;
        $timestamp = $ts->getTimestamp();
        $signature = $ts->generateSymmetricSignature(
            'POST',
            SnapConfig::DIRECT_DEBIT_PAYMENT_URL,
            $token,
            $req->generateJSONBody(),
            $timestamp,
            $cfg['secret_key']
        );
        $externalId = SnapHelper::generateExternalId();
        $header = SnapHelper::generateRequestHeaderDto(
            $timestamp,
            $signature,
            $cfg['client_id'],
            $externalId,
            'SDK',
            $token,
            null,
            null,
            null
        );
        try {
            $resp = (new DirectDebitServices)->doPaymentProcess($header, $req, $cfg['env']);
        } catch (\Throwable $e) {
            try {
                \Log::error('DOKU Checkout create error', ['message' => $e->getMessage()]);
            } catch (\Throwable $ignore) {
            }

            return ['error_code' => 'EXCEPTION', 'error_message' => $e->getMessage()];
        }
        if (is_object($resp) && property_exists($resp, 'webRedirectUrl') && ! empty($resp->webRedirectUrl)) {
            return [
                'redirect_url' => $resp->webRedirectUrl,
                'partner_reference_no' => $partnerReferenceNo,
                'channel' => 'DOKU_CHECKOUT',
            ];
        }
        try {
            \Log::warning('DOKU Checkout create failed', [
                'response' => is_array($resp) ? $resp : null,
            ]);
        } catch (\Throwable $ignore) {
        }
        if (is_array($resp)) {
            return [
                'error_code' => $resp['responseCode'] ?? null,
                'error_message' => $resp['responseMessage'] ?? null,
            ];
        }

        return null;
    }

    public function createTopupVa(string $koperasiId, array $anggota, int $amount, string $channel = 'VIRTUAL_ACCOUNT_BRI'): ?array
    {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return null;
        }
        $trxId = (string) Str::uuid();
        $path = '/doku-virtual-account/v2/payment-code';
        $bodyArr = [
            'order' => [
                'invoice_number' => $trxId,
                'amount' => (int) $amount,
            ],
            'virtual_account_info' => [
                'billing_type' => 'FIX_BILL',
                'expired_time' => 60,
                'reusable_status' => false,
                'info1' => 'Komera Topup',
            ],
            'customer' => [
                'name' => (string) ($anggota['nama'] ?? $anggota['nama_anggota'] ?? 'Anggota'),
                'email' => (string) ($anggota['email'] ?? ''),
            ],
        ];
        $body = json_encode($bodyArr);
        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $digest = base64_encode(hash('sha256', $body, true));
        $rawSig = 'Client-Id:'.$cfg['client_id']."\n".
            'Request-Id:'.$requestId."\n".
            'Request-Timestamp:'.$timestamp."\n".
            'Request-Target:'.$path."\n".
            'Digest:'.$digest;
        $sig = base64_encode(hash_hmac('sha256', $rawSig, $cfg['secret_key'], true));
        $headers = [
            'Client-Id' => $cfg['client_id'],
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => 'HMACSHA256='.$sig,
            'Content-Type' => 'application/json',
        ];
        try {
            $resp = Http::withHeaders($headers)->post($cfg['base_url'].$path, $bodyArr);
        } catch (\Throwable $e) {
            try {
                \Log::error('DOKU VA non-snap create error', ['message' => $e->getMessage()]);
            } catch (\Throwable $ignore) {
            }

            return ['error_code' => 'EXCEPTION', 'error_message' => $e->getMessage()];
        }
        $json = $resp->json();
        if ($resp->ok() && isset($json['virtual_account_info']['virtual_account_number'])) {
            return [
                'trx_id' => $trxId,
                'partner_service_id' => null,
                'customer_no' => null,
                'virtual_account_no' => $json['virtual_account_info']['virtual_account_number'] ?? null,
                'how_to_pay_page' => $json['virtual_account_info']['how_to_pay_page'] ?? null,
                'expired_date' => $json['virtual_account_info']['expired_date_utc'] ?? ($json['virtual_account_info']['expired_date'] ?? null),
                'amount' => (float) $amount,
                'channel' => 'DOKU_VA',
            ];
        }
        try {
            \Log::warning('DOKU VA non-snap create failed', [
                'status' => $resp->status(),
                'body' => $json,
            ]);
        } catch (\Throwable $ignore) {
        }

        return [
            'error_code' => (string) ($json['code'] ?? $resp->status()),
            'error_message' => (string) ($json['message'] ?? 'Failed to create VA'),
        ];
    }

    public function createCheckoutPayment(
        string $koperasiId,
        string $invoiceNumber,
        int $amount,
        array $customer = [],
        ?string $method = 'VA',
        ?string $subMethod = null,
        ?string $baseAppUrl = null,
        ?string $callbackPath = '/api/v1/kofood/payment/callback'
    ): array {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return ['success' => false, 'error_message' => 'DOKU config not found'];
        }
        $clientId = (string) ($cfg['client_id'] ?? '');
        $secretKey = (string) ($cfg['secret_key'] ?? '');
        $baseUrl = (string) ($cfg['base_url'] ?? '');
        if ($clientId === '' || $secretKey === '' || $baseUrl === '') {
            return ['success' => false, 'error_message' => 'DOKU config incomplete'];
        }
        $invoice = substr($invoiceNumber, 0, 64);
        $baseApp = $baseAppUrl ?: rtrim(\Illuminate\Support\Facades\URL::to('/'), '/');
        $skipParam = str_contains($baseApp, 'ngrok') ? '?ngrok-skip-browser-warning=1' : '';
        $resultUrl = $baseApp.'/pg/checkout/success';
        $cancelUrl = $baseApp.'/pg/checkout/failed';
        if ($skipParam !== '') {
            $resultUrl .= $skipParam;
            $cancelUrl .= $skipParam.'&cancel=1';
        } else {
            $cancelUrl .= '?cancel=1';
        }
        $resultUrl .= (str_contains($resultUrl, '?') ? '&' : '?').'order='.$invoice;
        $cancelUrl .= (str_contains($cancelUrl, '?') ? '&' : '?').'order='.$invoice;
        $rawPhone = (string) ($customer['phone'] ?? $customer['telepon'] ?? '');
        $digitsPhone = preg_replace('/\D+/', '', $rawPhone);
        if ($digitsPhone === null || strlen($digitsPhone) < 5 || strlen($digitsPhone) > 16) {
            $digitsPhone = '08123456789';
        }
        $payload = [
            'order' => [
                'amount' => (int) round($amount),
                'invoice_number' => $invoice,
                'currency' => 'IDR',
                'callback_url' => $baseApp.($callbackPath ?: '/api/v1/kofood/payment/callback'),
                'callback_url_result' => $resultUrl,
                'callback_url_cancel' => $cancelUrl,
                'auto_redirect' => true,
            ],
            'payment' => [
                'payment_due_date' => 60,
                'type' => 'SALE',
            ],
            'customer' => [
                'id' => (string) ($customer['id'] ?? 'GUEST'),
                'name' => (string) ($customer['name'] ?? $customer['nama'] ?? 'Guest'),
                'email' => (string) ($customer['email'] ?? 'guest@example.com'),
                'phone' => $digitsPhone,
                'address' => (string) ($customer['address'] ?? '-'),
                'country' => 'ID',
            ],
        ];
        $m = strtoupper((string) ($method ?? ''));
        $s = strtoupper((string) ($subMethod ?? ''));
        if ($m !== '') {
            $types = [];
            if ($m === 'QRIS') {
                $types = ['QRIS'];
            } elseif (in_array($m, ['DEBIT', 'CREDIT', 'CREDIT CARD', 'CARD', 'CREDIT_CARD'], true)) {
                $types = ['CREDIT_CARD'];
            } elseif (in_array($m, ['VA', 'TRANSFER', 'VIRTUAL_ACCOUNT'], true)) {
                $mapVa = [
                    'BCA' => 'VIRTUAL_ACCOUNT_BCA',
                    'MANDIRI' => 'VIRTUAL_ACCOUNT_BANK_MANDIRI',
                    'BSI' => 'VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI',
                    'BRI' => 'VIRTUAL_ACCOUNT_BRI',
                    'BNI' => 'VIRTUAL_ACCOUNT_BNI',
                    'PERMATA' => 'VIRTUAL_ACCOUNT_BANK_PERMATA',
                    'CIMB' => 'VIRTUAL_ACCOUNT_BANK_CIMB',
                    'DANAMON' => 'VIRTUAL_ACCOUNT_BANK_DANAMON',
                    'BTN' => 'VIRTUAL_ACCOUNT_BTN',
                    'BNC' => 'VIRTUAL_ACCOUNT_BNC',
                    'DOKU' => 'VIRTUAL_ACCOUNT_DOKU',
                ];
                $types = [$mapVa[$s] ?? 'VIRTUAL_ACCOUNT_DOKU'];
            } elseif (in_array($m, ['E_WALLET', 'E-MONEY', 'EMONEY'], true)) {
                $mapEm = [
                    'OVO' => 'EMONEY_OVO',
                    'SHOPEEPAY' => 'EMONEY_SHOPEEPAY',
                    'LINKAJA' => 'EMONEY_LINKAJA',
                    'DANA' => 'EMONEY_DANA',
                ];
                $types = [($mapEm[$s] ?? 'EMONEY_OVO')];
            } elseif (in_array($m, ['DIRECT DEBIT', 'DIRECT_DEBIT'], true)) {
                $mapDd = [
                    'BRI' => 'DIRECT_DEBIT_BRI',
                ];
                $types = [($mapDd[$s] ?? 'DIRECT_DEBIT_BRI')];
            }
            if (! empty($types)) {
                $payload['payment']['payment_method_types'] = $types;
            }
        }
        $targetPath = '/checkout/v1/payment';
        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $bodyStr = json_encode($payload);
        $digest = base64_encode(hash('sha256', $bodyStr, true));
        $stringToSign = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nRequest-Target:{$targetPath}\nDigest:{$digest}";
        $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));
        $headers = [
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $signature,
            'Digest' => 'SHA-256='.$digest,
            'Content-Type' => 'application/json',
        ];
        try {
            $resp = Http::withHeaders($headers)->post($baseUrl.$targetPath, $payload);
        } catch (\Throwable $e) {
            try {
                \Log::error('DOKU Checkout (HMAC) create error', ['message' => $e->getMessage()]);
            } catch (\Throwable $ignore) {
            }

            return ['success' => false, 'error_message' => $e->getMessage()];
        }
        $ok = $resp->successful();
        $json = $resp->json();
        try {
            \Log::info('DOKU Checkout (HMAC) response', ['status' => $resp->status(), 'json' => $json]);
        } catch (\Throwable $ignore) {
        }
        if (! $ok) {
            $err = is_array($json) ? ((string) ($json['message'][0] ?? $json['message'] ?? $resp->body())) : $resp->body();

            return ['success' => false, 'status' => $resp->status(), 'error_message' => $err, 'response' => $json];
        }
        $data = (array) ($json['response'] ?? []);
        $pay = (array) ($data['payment'] ?? []);
        $paymentUrl = (string) (
            $pay['url'] ??
            ($json['response']['redirect_url'] ?? '') ??
            ($json['redirect_url'] ?? '') ??
            ($json['response']['payment']['payment_url'] ?? '') ??
            ($json['payment']['payment_url'] ?? '')
        );
        $qrCode = (string) (
            ($pay['qr_code'] ?? '') ?:
            ($json['response']['qr_code'] ?? '') ?:
            ($json['payment']['qr_code'] ?? '')
        );

        return [
            'success' => true,
            'invoice_number' => $invoice,
            'payment_url' => $paymentUrl,
            'qr_code' => $qrCode,
            'status' => 'PENDING',
            'raw' => $json,
        ];
    }

    public function checkVaStatus(
        string $koperasiId,
        string $partnerServiceId,
        string $customerNo,
        string $virtualAccountNo,
        ?string $inquiryRequestId = null,
        ?string $paymentRequestId = null
    ): ?array {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return null;
        }
        $invoice = $inquiryRequestId ?: $paymentRequestId ?: $virtualAccountNo;
        if (! $invoice) {
            return null;
        }
        $path = '/orders/v1/status/'.urlencode($invoice);
        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $rawSig = 'Client-Id:'.$cfg['client_id']."\n".
            'Request-Id:'.$requestId."\n".
            'Request-Timestamp:'.$timestamp."\n".
            'Request-Target:'.$path;
        $sig = base64_encode(hash_hmac('sha256', $rawSig, $cfg['secret_key'], true));
        $headers = [
            'Client-Id' => $cfg['client_id'],
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => 'HMACSHA256='.$sig,
        ];
        try {
            $resp = Http::withHeaders($headers)->get($cfg['base_url'].$path);
        } catch (\Throwable $e) {
            return null;
        }
        $json = $resp->json();
        if ($resp->ok() && isset($json['transaction']['status'])) {
            $status = (string) ($json['transaction']['status'] ?? '');
            $amount = isset($json['order']['amount']) ? (float) $json['order']['amount'] : 0.0;
            $paid = strcasecmp($status, 'SUCCESS') === 0 ? $amount : 0.0;

            return [
                'status' => $status,
                'paid_value' => $paid,
                'paid_currency' => 'IDR',
                'bill_value' => $amount,
                'bill_currency' => 'IDR',
                'trx_id' => $json['order']['invoice_number'] ?? null,
            ];
        }

        return null;
    }
}
