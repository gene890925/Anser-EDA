<?php
require_once __DIR__ . '/vendor/autoload.php';

use SDPMlab\AnserEDA\MessageQueue\RabbitMQConnection;
use SDPMlab\AnserEDA\HandlerScanner;

//**初始化 RabbitMQ 連線**
$rabbitMQ = new RabbitMQConnection('127.0.0.1', 5672, 'root', 'root');
$channel = $rabbitMQ->getChannel();

//**確保 Exchange & Queue 存在**
$exchangeName = 'events';
$channel->exchange_declare($exchangeName, 'direct', false, true, false);

// 使用 HandlerScanner 自動掃描並設定 RabbitMQ Queue
$handlerScanner = new HandlerScanner();
$sagaFilePath = __DIR__ . '/Sagas/OrderSaga.php';

// 一次完成掃描和設定
$queueNames = $handlerScanner->scanAndSetupQueues($sagaFilePath, $rabbitMQ, $exchangeName);
// 顯示結果
$handlerScanner->displaySetupResults($queueNames);
