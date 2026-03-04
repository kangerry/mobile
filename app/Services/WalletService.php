<?php

namespace App\Services;

class WalletService
{
    public function credit(string $memberId, int $amount): array
    {
        return ['member_id' => $memberId, 'credited' => $amount];
    }

    public function debit(string $memberId, int $amount): array
    {
        return ['member_id' => $memberId, 'debited' => $amount];
    }
}
