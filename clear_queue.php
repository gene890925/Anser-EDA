<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

class ClearAllQueues
{
    private $connection;
    private $channel;

    public function __construct()
    {
        // 連接 RabbitMQ
        $this->connection = new AMQPStreamConnection('127.0.0.1', 5672, 'root', 'root');
        $this->channel = $this->connection->channel();
    }

    public function purgeAllQueues()
    {
        try {
            // 取得所有 Queue 列表
            $queues = $this->listQueues();

            if (empty($queues)) {
                echo "⚠ 沒有發現任何 Queue\n";
                return;
            }

            foreach ($queues as $queue) {
                $queueName = $queue['name'];
                $this->channel->queue_purge($queueName);
                echo "✅ 清除 Queue: '{$queueName}' 內的所有訊息\n";
            }

            echo "🎉 所有 Queue 內的訊息已清除！\n";

        } catch (\Exception $e) {
            echo "❌ 清除 Queue 失敗：" . $e->getMessage() . "\n";
        }
    }

    // 取得所有 Queue 清單
    private function listQueues()
    {
        $apiUrl = 'http://127.0.0.1:15672/api/queues';
        $username = 'root';
        $password = 'root';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: [];
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}

// ✅ 執行清除所有 Queue
$clearQueues = new ClearAllQueues();
$clearQueues->purgeAllQueues();
$clearQueues->close();
