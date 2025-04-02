<?php
namespace App\Framework\MessageQueue;

class Consumer
{
    private $channel;
    private $eventBus;

    public function __construct($channel, $eventBus)
    {
        $this->channel = $channel;
        $this->eventBus = $eventBus;
    }

    public function consume(string $queue)
    {
        echo " [*] Waiting for messages in queue: $queue\n";

        $callback = function ($msg) {
            $eventData = json_decode($msg->body, true);
            if (!$eventData || !isset($eventData['type'])) {
                echo " [x] Invalid message format\n";
                return;
            }

            $eventType = $eventData['type'];

            echo " [x] Received event: $eventType\n";

            if (class_exists($eventType)) {
                $eventObject = new $eventType(...array_values($eventData['data'])); 
                $this->eventBus->dispatch($eventObject);
            }

            //確保 `basic_ack()` 只執行一次
            if ($msg->has('delivery_tag')) {
                echo " [x] Acknowledging message: $eventType\n";
               $this->channel->basic_ack($msg->get('delivery_tag'));
            } else {
                echo " [x] Warning: Message does not have a valid delivery tag.\n";
            }
        };

        //確保 `no_ack = false`，RabbitMQ 只發送一次
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        while (true) {
            try {
                $this->channel->wait();
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                echo " [x] Timeout: " . $e->getMessage() . "\n";
            }
        }
    }
}
