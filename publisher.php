<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Framework\MessageQueue\RabbitMQConnection;
use App\Framework\MessageQueue\MessageBus;
use App\Events\OrderCreateRequestedEvent;

// 設定適當的標頭
header("Content-Type: application/json");

// 確保是 POST 請求

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}


// 讀取請求的 `raw JSON body`
$input = json_decode(file_get_contents('php://input'), true);

// 確保 `productList` 存在並且是陣列

if (!isset($input) || !is_array($input)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request: productList is required and must be an array"]);
    exit;
}


// 連接 RabbitMQ
try {
    $rabbitConnection = new RabbitMQConnection('rabbitmq', 5672, 'root', 'root');
    $channel = $rabbitConnection->getChannel();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "RabbitMQ connection failed", "details" => $e->getMessage()]);
    exit;
}

// 初始化 MessageBus
$messageBus = new MessageBus($channel);

// 發送事件 **只包含 productList**
// 發送事件 (確保 `productList` 為陣列)
$messageBus->publishMessage('events', json_encode([
    'type' => OrderCreateRequestedEvent::class,
    'data' => [
        'productList' => array_values($input) // ✅ 確保是陣列
    ]
]));

echo json_encode(["message" => "Event sent successfully"]);
