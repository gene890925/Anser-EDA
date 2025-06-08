<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use App\Framework\EventBus;
use App\Framework\EventStore\EventStoreDB;
use App\Framework\MessageQueue\MessageBus;
use App\Framework\MessageQueue\RabbitMQConnection;
use App\Events\OrderCreateRequestedEvent;
use Dotenv\Dotenv;

// 載入環境變數
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 設定適當的標頭
header("Content-Type: application/json");

// 創建 Worker 實例，監聽端口 9000
$worker = new Worker("http://0.0.0.0:9000");  // Workerman 服務監聽端口
$worker->count = 10; // 設置為 10 個進程，可以根據需要調整

// 在 Worker 啟動時建立 RabbitMQ 連線和 EventBus
$rabbitMQ = new RabbitMQConnection(
    $_ENV['RABBITMQ_HOST'],
    $_ENV['RABBITMQ_PORT'],
    $_ENV['RABBITMQ_USER'],
    $_ENV['RABBITMQ_PASSWORD']
);
$channel = $rabbitMQ->getChannel();
$messageBus = new MessageBus($channel);
$eventStoreDB = new EventStoreDB(
    $_ENV['EVENTSTORE_HOST'],
    $_ENV['EVENTSTORE_PORT'],
    $_ENV['EVENTSTORE_USER'],
    $_ENV['EVENTSTORE_PASSWORD']
);
$eventBus = new EventBus($messageBus, $eventStoreDB);

// 持續運行並處理來自 HTTP 請求的事件
$worker->onMessage = function ($connection, $data) use ($eventBus) {
    // 記錄收到的原始數據
    error_log("Received data: " . $data);

    // 嘗試解析數據
    $input = json_decode($data, true);

    // 檢查解析結果，並查看是否為陣列
    if (!is_array($input)) {
        // 返回錯誤消息並顯示收到的原始數據
        $connection->send(json_encode([
            "error" => "Invalid request: Data must be an array", 
            "received_data" => $data  // 返回原始數據以供調試
        ]));
        return;
    }

    // 確保資料格式正確，這是你的業務邏輯要求
    if (!isset($input[0]['p_key']) || !isset($input[0]['price']) || !isset($input[0]['amount'])) {
        $connection->send(json_encode(["error" => "Invalid data format: Missing required fields"]));
        return;
    }

    // 發送事件到 RabbitMQ 和 EventStoreDB
    $eventBus->publish(OrderCreateRequestedEvent::class, [
        'productList' => $input  // 直接將 input 資料作為 productList 發送
    ]);

    // 返回成功訊息
    $connection->send(json_encode(["message" => "Event sent successfully"]));
};

// 啟動 Worker
Worker::runAll();
