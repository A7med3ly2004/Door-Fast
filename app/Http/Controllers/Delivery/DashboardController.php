<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Base\BaseDeliveryDashboardController;

class DashboardController extends BaseDeliveryDashboardController
{
    protected function viewPrefix(): string
    {
        return 'delivery';
    }

    // No extra fields needed for standard delivery dashboard
}
