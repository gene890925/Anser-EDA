<?php
require_once __DIR__ . '/vendor/autoload.php';

use SDPMlab\AnserEDA\MessageQueue\RabbitMQConnection;

// ✅ **初始化 RabbitMQ 連線**
$rabbitMQ = new RabbitMQConnection('127.0.0.1', 5672, 'root', 'root');
$channel = $rabbitMQ->getChannel();

// ✅ **確保 Exchange & Queue 存在**
$exchangeName = 'events';
$channel->exchange_declare($exchangeName, 'direct', false, true, false);

$queueNames = ['OrderCreateRequestedEvent', 'OrderCreatedEvent', 'InventoryDeductedEvent', 'PaymentProcessedEvent', 'OrderCompletedEvent', 'RollbackInventoryEvent','RollbackOrderEvent'];

foreach ($queueNames as $queueName) {
    $rabbitMQ->setupQueue($queueName, $exchangeName, $queueName);
}

echo "✅ RabbitMQ 設定完成";
