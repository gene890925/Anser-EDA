<?php
namespace App\Framework\MessageQueue;

use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class Consumer
{
    private $channel;
    private $eventBus;
    private $startTime;
    private $firstMessageProcessed = false;
    private $logFile;
    private $queueName;
    private $logger;

    public function __construct($channel, $eventBus, ContainerInterface $container)
    {
        $this->channel = $channel;
        $this->eventBus = $eventBus;
        $this->startTime = microtime(true);
        $this->logFile = dirname(__DIR__, 2) . '/Logs/consumer.log';
        $this->logger = $container->get(LoggerFactory::class)->get('performance');
        
        // 確保日誌目錄存在
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }

        // 記錄容器啟動時間
        $this->logger->info('Container started at: ' . date('Y-m-d H:i:s'));
    }

    private function log($message)
    {
        file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND);
    }

    public function consume(string $queue)
    {
        $this->queueName = $queue;
        
        $callback = function ($msg) {
            if (!$this->firstMessageProcessed) {
                $timeToFirstMessage = microtime(true) - $this->startTime;
                $this->logger->info(sprintf(
                    'Queue: %s - Time to first message: %.2f seconds',
                    $this->queueName,
                    $timeToFirstMessage
                ));
                $this->firstMessageProcessed = true;
            }

            $eventData = json_decode($msg->body, true);
            if (!$eventData || !isset($eventData['type'])) {
                return;
            }

            $eventType = $eventData['type'];

            if (class_exists($eventType)) {
                $eventObject = new $eventType(...array_values($eventData['data'])); 
                $this->eventBus->dispatch($eventObject);
                
                // 記錄交易完成時間
                $totalTime = microtime(true) - $this->startTime;
                $this->logger->info(sprintf(
                    'Queue: %s - Transaction completed in %.2f seconds',
                    $this->queueName,
                    $totalTime
                ));
            }

            if ($msg->has('delivery_tag')) {
                $this->channel->basic_ack($msg->get('delivery_tag'));
            }
        };

        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        while (true) {
            try {
                $this->channel->wait();
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // 忽略超時異常
            }
        }
    }
}
