<?php
namespace App\Events;

class RollbackPaymentEvent
{
    public string $orderId;
    public string $userKey;
    public float $amount;

    public function __construct(string $orderId, string $userKey, float $amount)
    {
        $this->orderId = $orderId;
        $this->userKey = $userKey;
        $this->amount = $amount;
    }
}
