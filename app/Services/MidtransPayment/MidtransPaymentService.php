<?php

namespace App\Services\MidtransPayment;

use LaravelEasyRepository\BaseService;

interface MidtransPaymentService extends BaseService
{
    /**
     * Initialize Midtrans configuration.
     */
    public function initializeMidtransConfig();
}
