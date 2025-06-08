<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Framework\EventStore\EventStoreDB;


$eventStore = new EventStoreDB();
$eventStore->createProjection();

/*

$eventStore = new EventStoreDB();

$eventData = [
    'eventType' => 'OrderCreated',
    'data' => [
        'orderId' => 'ORDER_1234',
        'userId' => 'USER_5678',
        'total' => 1500
    ]
];

$eventStore->appendEvent('test_events', $eventData);
*/

/*
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ramsey\Uuid\Uuid;

try {
    // 初始化 HTTP 客戶端
    $client = new Client([
        'base_uri' => 'http://localhost:2113',
        'timeout'  => 5.0
    ]);

    // ✅ 生成 UUID 作為事件 ID（EventStoreDB 需要）
    $eventId = Uuid::uuid4()->toString();

    // ✅ 事件資料
    $eventData = [
        'eventId'   => $eventId, // 確保是 UUID
        'eventType' => 'OrderCreated',
        'data'      => [
            'orderId' => 'ORDER_' . rand(1000, 9999),
            'userId'  => 'USER_' . rand(1, 10),
            'amount'  => rand(1, 5) * 1000
        ],
        'metadata'  => new stdClass() // Metadata 不能是 null，至少要是空物件
    ];

    // ✅ **EventStoreDB 需要事件包裝成陣列**
    $eventPayload = [$eventData];

    // 發送 HTTP POST 請求到 EventStoreDB
    $response = $client->post('/streams/order_events', [
        'auth'    => ['admin', 'changeit'], // 預設帳密
        'headers' => [
            'Content-Type'     => 'application/vnd.eventstore.events+json',
            'ES-ExpectedVersion' => '-2'
        ],
        'json' => $eventPayload
    ]);

    echo "✅ 事件成功寫入 EventStoreDB！\n";
    echo "🔹 狀態碼：" . $response->getStatusCode() . "\n";
    echo "🔹 事件內容：" . json_encode($eventData, JSON_PRETTY_PRINT) . "\n";

} catch (RequestException $e) {
    echo "❌ 發送事件失敗：" . $e->getMessage() . "\n";
}

*/