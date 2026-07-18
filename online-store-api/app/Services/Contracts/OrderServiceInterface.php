<?php

namespace App\Services\Contracts;

use App\Models\Order;

interface OrderServiceInterface
{
    public function placeOrder(array $items): Order;
}
