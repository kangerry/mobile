<?php

namespace Doku\Snap\Models\PaymentJumpApp;

class PaymentJumpAppAdditionalInfoResponseDto
{
    public ?string $webRedirectUrl;

    public function __construct(
        ?string $webRedirectUrl,
    ) {
        $this->webRedirectUrl = $webRedirectUrl;
    }
}
