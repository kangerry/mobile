<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use Doku\Snap\Controllers\VaController;
use Doku\Snap\Models\TotalAmount\TotalAmount;
use Doku\Snap\Models\VA\AdditionalInfo\CreateVaRequestAdditionalInfo;
use Doku\Snap\Models\VA\AdditionalInfo\Origin;
use Doku\Snap\Models\VA\Request\CreateVaRequestDto;
use Doku\Snap\Models\VA\VirtualAccountConfig\CreateVaVirtualAccountConfig;
use Doku\Snap\Services\TokenServices;

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

    public function createTopupVa(string $koperasiId, array $anggota, int $amount, string $channel = 'VIRTUAL_ACCOUNT_BRI'): ?array
    {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return null;
        }
        if (empty($cfg['private_key'])) {
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
        $token = $this->getTokenB2B($cfg) ?: ($cfg['env_name'] === 'sandbox' ? ($cfg['api_key'] ?? null) : null);
        if (! $token) return null;

        $digits = preg_replace('/\D/', '', (string) $cfg['client_id']);
        $partnerServiceId = strlen($digits) >= 8 ? substr($digits, -8) : str_pad($digits, 8, '0', STR_PAD_LEFT);
        $customerNo = str_pad((string) ($anggota['id'] ?? '0'), 20, '0', STR_PAD_LEFT);
        $virtualAccountNo = $partnerServiceId.$customerNo;
        $trxId = (string) Str::uuid();
        $total = new TotalAmount(number_format((float) $amount, 2, '.', ''), 'IDR');
        $config = new CreateVaVirtualAccountConfig(false);
        $origin = new Origin('PHP', '1.0.0', 'komera-backend');
        $addInfo = new CreateVaRequestAdditionalInfo($channel, $config, $origin);
        $req = new CreateVaRequestDto(
            $partnerServiceId,
            $customerNo,
            $virtualAccountNo,
            (string) ($anggota['nama'] ?? $anggota['nama_anggota'] ?? 'Anggota'),
            (string) ($anggota['email'] ?? ''),
            (string) ($anggota['telepon'] ?? ''),
            $trxId,
            $total,
            $addInfo,
            'C',
            gmdate('c', strtotime('+1 day')),
            [
                ['english' => 'Top up E-Wallet', 'indonesia' => 'Top up Dompet'],
            ]
        );

        try {
            $vaCtrl = new VaController;
            $resp = $vaCtrl->createVa($req, $cfg['private_key'] ?? '', $cfg['client_id'], $token, $cfg['secret_key'], $cfg['env']);
        } catch (\Throwable $e) {
            try {
                \Log::error('DOKU VA create error', ['message' => $e->getMessage()]);
            } catch (\Throwable $ignore) {
            }
            return ['error_code' => 'EXCEPTION', 'error_message' => $e->getMessage()];
        }

        if ($resp && $resp->virtualAccountData) {
            return [
                'trx_id' => $trxId,
                'partner_service_id' => $partnerServiceId,
                'customer_no' => $customerNo,
                'virtual_account_no' => $resp->virtualAccountData->virtualAccountNo,
                'how_to_pay_page' => $resp->virtualAccountData->additionalInfo->howToPayPage ?? null,
                'expired_date' => $resp->virtualAccountData->expiredDate ?? null,
                'amount' => (float) $amount,
                'channel' => $channel,
            ];
        }

        try {
            \Log::warning('DOKU VA create failed', [
                'response_code' => $resp->responseCode ?? null,
                'response_message' => $resp->responseMessage ?? null,
                'partner_service_id' => $partnerServiceId,
                'customer_no' => $customerNo,
            ]);
        } catch (\Throwable $ignore) {
        }

        return [
            'error_code' => $resp->responseCode ?? null,
            'error_message' => $resp->responseMessage ?? null,
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
        if (empty($cfg['private_key'])) {
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
        $token = $this->getTokenB2B($cfg);
        if (! $token) {
            return null;
        }
        $dtoClass = '\\Doku\\Snap\\Models\\VA\\Request\\CheckStatusVaRequestDto';
        $req = new $dtoClass(
            $partnerServiceId,
            $customerNo,
            $virtualAccountNo,
            $inquiryRequestId,
            $paymentRequestId,
            null
        );
        $vaCtrl = new VaController;
        $resp = $vaCtrl->doCheckStatusVa($req, $cfg['secret_key'], $cfg['client_id'], $token, $cfg['env']);
        if ($resp && $resp->virtualAccountData) {
            return [
                'paid_value' => (float) ($resp->virtualAccountData->paidAmount->value ?? 0),
                'paid_currency' => $resp->virtualAccountData->paidAmount->currency ?? 'IDR',
                'bill_value' => (float) ($resp->virtualAccountData->billAmount->value ?? 0),
                'bill_currency' => $resp->virtualAccountData->billAmount->currency ?? 'IDR',
                'trx_id' => $resp->virtualAccountData->trxId ?? null,
                'payment_flag_reason' => [
                    'en' => data_get($resp, 'virtualAccountData.paymentFlagReason.english'),
                    'id' => data_get($resp, 'virtualAccountData.paymentFlagReason.indonesia'),
                ],
            ];
        }

        return null;
    }
}
