<?php

namespace App\Events;

class OrderCompletedEvent
{
    public string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }
}