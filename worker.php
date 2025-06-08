<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use App\Framework\EventBus;
use App\Framework\EventStore\EventStoreDB;
use App\Framework\MessageQueue\MessageBus;
use App\Framework\MessageQueue\RabbitMQConnection;
use App\Events\OrderCreateRequestedEvent;

// 設定適當的標頭
header("Content-Type: application/json");

$worker = new Worker("http://0.0.0.0:9000");

$worker->count = 10;


$worker->onMessage = function ($connection, $request) use ($eventBus) {
  
    $input = json_decode($body, true);

    // 發送事件到 RabbitMQ 和 EventStoreDB
    $eventBus->publish(OrderCreateRequestedEvent::class, [
        'productList' => $input
    ]);

    // 返回成功訊息
    $connection->send(json_encode([
        "message" => "Event sent successfully",
        "status" => "200"
    ]));
};

Worker::runAll();
