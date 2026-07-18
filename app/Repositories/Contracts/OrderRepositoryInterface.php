<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function createOrderWithItems(float $totalAmount, array $items): Order;
}
