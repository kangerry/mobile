<?php

namespace App\Services;

class DriverMatchingService
{
    public function findDriver(array $location): array
    {
        return ['driver_id' => 'DRV-001', 'location' => $location];
    }
}
