<?php

namespace App\Repositories\MidtransPayment;

use LaravelEasyRepository\Implementations\Eloquent;
use Midtrans\Config;
use Midtrans\Snap;

// use App\Models\MidtransPayment;

class MidtransPaymentRepositoryImplement extends Eloquent implements MidtransPaymentRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    // protected $model;

    public function __construct()
    {
        // $this->model = $model;
    }

    /**
     * Initialize Midtrans configuration.
     */
    public function initializeMidtransConfig() {
        // Set your Merchant Server Key
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        // Set sanitization on (default)
        Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        Config::$is3ds = true;
    }

}
