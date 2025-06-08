<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Framework\EventBus;
use App\Framework\EventStore\EventStoreDB;
use App\Framework\MessageQueue\MessageBus;
use App\Framework\MessageQueue\Consumer;
use App\Framework\MessageQueue\RabbitMQConnection;
use App\Framework\HandlerScanner;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;

// 載入環境變數
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

//  **檢查是否有傳入 queue_name**
if ($argc < 2) {
    die(" 輸入監聽的佇列名稱！\n用法: php consumer.php orders_queue\n");
}

$queueName = $argv[1]; // 傳入的佇列名稱

//  **初始化 RabbitMQ 連線**
$rabbitMQ = new RabbitMQConnection(
    $_ENV['RABBITMQ_HOST'],
    $_ENV['RABBITMQ_PORT'],
    $_ENV['RABBITMQ_USER'],
    $_ENV['RABBITMQ_PASSWORD']
);
$channel = $rabbitMQ->getChannel();
$channel->basic_qos(null, 1000, null);  

//  **初始化 MessageBus & EventBus**
$messageBus = new MessageBus($channel);
$eventStoreDB = new EventStoreDB(
    $_ENV['EVENTSTORE_HOST'],
    $_ENV['EVENTSTORE_PORT'],
    $_ENV['EVENTSTORE_USER'],
    $_ENV['EVENTSTORE_PASSWORD']
);
$eventBus = new EventBus($messageBus, $eventStoreDB);

$scanner = new HandlerScanner();
$scanner->scanAndRegisterHandlers('App\Sagas', $eventBus);

//  **啟動 RabbitMQ 消費者**
echo " [*] Listening on queue: $queueName\n";
$consumer = new Consumer($channel, $eventBus);
$consumer->consume($queueName);
    