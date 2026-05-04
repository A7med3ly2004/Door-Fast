<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Base\BaseDeliveryDashboardController;

class DashboardController extends BaseDeliveryDashboardController
{
    protected function viewPrefix(): string
    {
        return 'reserve_delivery';
    }

    /**
     * Reserve delivery dashboard also returns capacity_count
     */
    protected function extraData(array $base): array
    {
        return [
            'capacity_count' => $base['delivered_count'] + $base['received_count'],
        ];
    }
}
