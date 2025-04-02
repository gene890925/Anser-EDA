<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Framework\EventBus;
use App\Framework\EventStore\EventStoreDB;
use App\Framework\MessageQueue\MessageBus;
use App\Framework\MessageQueue\Consumer;
use App\Framework\MessageQueue\RabbitMQConnection;
use App\Framework\HandlerScanner;

// ✅ **檢查是否有傳入 queue_name**
if ($argc < 2) {
    die("❌ 錯誤：請指定要監聽的隊列名稱！\n用法: php consume.php orders_queue\n");
}

$queueName = $argv[1]; // 讀取 CLI 傳入的隊列名稱

// ✅ **初始化 RabbitMQ 連線**
$rabbitMQ = new RabbitMQConnection('127.0.0.1', 5672, 'root', 'root');
$channel = $rabbitMQ->getChannel();

// ✅ **初始化 MessageBus & EventBus**
$messageBus = new MessageBus($channel);
$eventStoreDB = new EventStoreDB();
$eventBus = new EventBus($messageBus, $eventStoreDB);

$scanner = new HandlerScanner();
$scanner->scanAndRegisterHandlers('App\Sagas', $eventBus);

// ✅ **啟動 RabbitMQ 消費者**
echo " [*] Listening on queue: $queueName\n";
$consumer = new Consumer($channel, $eventBus);
$consumer->consume($queueName);
    