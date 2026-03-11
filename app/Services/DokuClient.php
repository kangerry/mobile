<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        return [
            'env_name' => $row->env === 'production' ? 'production' : 'sandbox',
            'env' => $row->env === 'production' ? 'true' : 'false',
            'client_id' => $row->client_id,
            'secret_key' => $row->secret_key,
            'private_key' => $row->private_key ?? '',
            'public_key' => $row->public_key,
            'base_url' => rtrim((string) $row->base_url, '/'),
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

    protected function getTokenB2B(array $cfg): ?string
    {
        $tokenSvc = new TokenServices;
        $timestamp = $tokenSvc->getTimestamp();
        $signature = $tokenSvc->createSignature($cfg['private_key'], $cfg['client_id'], $timestamp);
        $req = $tokenSvc->createTokenB2BRequestDto($signature, $timestamp, $cfg['client_id']);
        $resp = $tokenSvc->createTokenB2B($req, $cfg['env']);

        return $resp->accessToken ?? null;
    }

    public function createTopupVa(string $koperasiId, array $anggota, int $amount, string $channel = 'VIRTUAL_ACCOUNT_BRI'): ?array
    {
        $cfg = $this->resolveSettings($koperasiId);
        if (! $cfg) {
            return null;
        }
        $token = $this->getTokenB2B($cfg);
        if (! $token) {
            return null;
        }

        $partnerServiceId = str_pad(preg_replace('/\D/', '', (string) $cfg['client_id']), 8, '0', STR_PAD_LEFT);
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
            $resp = $vaCtrl->createVa($req, $cfg['private_key'], $cfg['client_id'], $token, $cfg['secret_key'], $cfg['env']);
        } catch (\Throwable $e) {
            try {
                \Log::error('DOKU VA create error', ['message' => $e->getMessage()]);
            } catch (\Throwable $ignore) {
            }
            return null;
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

        return null;
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
