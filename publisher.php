<?php
require_once __DIR__ . '/vendor/autoload.php';

use SDPMlab\AnserEDA\EventBus;
use SDPMlab\AnserEDA\EventStore\EventStoreDB;
use SDPMlab\AnserEDA\MessageQueue\MessageBus;
use SDPMlab\AnserEDA\MessageQueue\RabbitMQConnection;
use App\Events\OrderCreateRequestedEvent;

header("Content-Type: application/json");

$rabbitMQ = new RabbitMQConnection('rabbitmq', 5672, 'root', 'root');
$channel = $rabbitMQ->getChannel();

$messageBus = new MessageBus($channel);
$eventStoreDB = new EventStoreDB('eventstoredb',2113,'admin','changeit');
$eventBus = new EventBus($messageBus, $eventStoreDB);

// 嘗試解析數據
$data = file_get_contents('php://input');
$input = json_decode($data, true);

// 發送事件到 RabbitMQ 和 EventStoreDB
$eventBus->publish(OrderCreateRequestedEvent::class, [
    'productList' => $input  // 直接將 input 資料作為 productList 發送
]);

// 返回成功訊息
echo json_encode(["message" => "Event sent successfully","data"=>$input]);


