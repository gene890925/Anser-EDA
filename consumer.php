<?php

require_once __DIR__ . '/vendor/autoload.php';

use SDPMlab\AnserEDA\EventBus;
use SDPMlab\AnserEDA\EventStore\EventStoreDB;
use SDPMlab\AnserEDA\MessageQueue\MessageBus;
use SDPMlab\AnserEDA\MessageQueue\Consumer;
use SDPMlab\AnserEDA\MessageQueue\RabbitMQConnection;
use SDPMlab\AnserEDA\HandlerScanner;

//  **檢查是否有傳入 queue_name**
if ($argc < 2) {
    die(" 輸入監聽的佇列名稱！\n用法: php consumer.php orders_queue\n");
}

$queueName = $argv[1]; // 傳入的佇列名稱
$rabbitMQ = new RabbitMQConnection('127.0.0.1', 5672, 'root', 'root');
$channel = $rabbitMQ->getChannel();
$channel->basic_qos(null, 1000, null);  
$messageBus = new MessageBus($channel);
$eventStoreDB = new EventStoreDB('127.0.0.1',2113,'admin','changeit');
$eventBus = new EventBus($messageBus, $eventStoreDB);

$scanner = new HandlerScanner();
$scanner->scanAndRegisterHandlers('App\Sagas', $eventBus);

//  **啟動 RabbitMQ 消費者**
echo " [*] Listening on queue: $queueName\n";
$consumer = new Consumer($channel, $eventBus);
$consumer->consume($queueName);

    