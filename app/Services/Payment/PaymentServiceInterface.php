<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentServiceInterface
{
    public function createInvoice(Order $order): array;
}
