<?php

namespace App\Repositories\MidtransPayment;

use LaravelEasyRepository\Repository;

interface MidtransPaymentRepository extends Repository
{
    /**
     * Initialize Midtrans configuration.
     */
    public function initializeMidtransConfig();
}
