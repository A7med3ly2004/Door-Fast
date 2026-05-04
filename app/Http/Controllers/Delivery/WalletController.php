<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Base\BaseDeliveryWalletController;

class WalletController extends BaseDeliveryWalletController
{
    protected function viewPrefix(): string
    {
        return 'delivery';
    }
}
