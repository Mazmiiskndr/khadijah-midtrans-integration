<?php

namespace App\Services\MidtransPayment;

use LaravelEasyRepository\Service;
use App\Repositories\MidtransPayment\MidtransPaymentRepository;
use Illuminate\Support\Facades\Log;

class MidtransPaymentServiceImplement extends Service implements MidtransPaymentService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected $mainRepository;

    public function __construct(MidtransPaymentRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }

    /**
     * Initialize Midtrans configuration.
     */
    public function initializeMidtransConfig()
    {
        try {
            return $this->mainRepository->initializeMidtransConfig();
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            throw $th;
        }
    }
}
