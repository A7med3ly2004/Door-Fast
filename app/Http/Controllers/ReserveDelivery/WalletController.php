<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Base\BaseDeliveryWalletController;

class WalletController extends BaseDeliveryWalletController
{
    protected function viewPrefix(): string
    {
        return 'reserve_delivery';
    }
}
