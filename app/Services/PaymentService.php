<?php

namespace App\Services;

class PaymentService
{
    public function charge(array $payload): array
    {
        return ['status' => 'queued'] + $payload;
    }

    public function refund(string $transactionId, int $amount): array
    {
        return ['transaction_id' => $transactionId, 'refunded' => $amount];
    }
}
