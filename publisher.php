<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
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


// 檢查解析結果，並查看是否為陣列
if (!is_array($input)) {
    echo json_encode([
        "error" => "Invalid request: Data must be an array",
        "received_data" => $data
    ]);
    http_response_code(400);
    return;
}

// 檢查每一個商品欄位
foreach ($input as $item) {
    if (!isset($item['p_key']) || !isset($item['price']) || !isset($item['amount'])) {
        echo json_encode(["error" => "Invalid data format: Missing required fields in one of the products"]);
        http_response_code(400);
        return;
    }
    if (!is_int($item['price'])) {
        echo json_encode(["error" => "Invalid data format: price must be int in all products"]);
        http_response_code(400);
        return;
    }
}

// 確保資料格式正確，這是你的業務邏輯要求
if (!isset($input[0]['p_key']) || !isset($input[0]['price']) || !isset($input[0]['amount'])) {
    echo json_encode(["error" => "Invalid data format: Missing required fields"]);
    http_response_code(400);
    return;
}

// 發送事件到 RabbitMQ 和 EventStoreDB
$eventBus->publish(OrderCreateRequestedEvent::class, [
    'productList' => $input  // 直接將 input 資料作為 productList 發送
]);

// 返回成功訊息
echo json_encode(["message" => "Event sent successfully","data"=>$input]);

