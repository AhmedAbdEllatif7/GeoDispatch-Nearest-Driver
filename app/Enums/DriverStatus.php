<?php

namespace App\Enums;

enum DriverStatus: string
{
    case AVAILABLE = 'available';
    case BUSY = 'busy';
    case OFFLINE = 'offline';

    /**
     * Get all string values of the enum cases.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if the driver is eligible to receive dispatches.
     */
    public function canReceiveRides(): bool
    {
        return $this === self::AVAILABLE;
    }
}
