<?php

namespace Doku\Snap\Models\Notification;

class PaymentNotificationResponseHeaderDto
{
    public string $xTimestamp;

    public string $contentType = 'application/json';

    public function __construct(string $xTimestamp)
    {
        $this->xTimestamp = $xTimestamp;
    }

    public function generateJSONHeader(): string
    {
        $payload = [
            'xTimestamp' => $this->xTimestamp,
        ];

        return json_encode($payload);
    }
}
