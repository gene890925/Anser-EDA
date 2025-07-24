# 在 docker-compose 檔資料夾下以下這行指令建置環境
輸入`docker-compose up -d`

# 進入容器內
輸入 `docker-compose exec app bash`

# 安裝套件
輸入 `composer install`

# 使用Postman執行發送publisher.php請求
使用POST的Body改成raw `[ { "p_key": 1, "price": 1, "amount": 1 }, { "p_key": 2, "price": 1, "amount": 1 }]`

# 設定RabbitMQ
`php mqset.php`

# 在檔案資料夾 php consumer.php 執行Event名稱
接收需求  
`php consumer.php OrderCreateRequestedEvent`

訂單完成  
`php consumer.php OrderCreatedEvent`

扣庫存完成  
`php consumer.php InventoryDeductedEvent`

扣款完成  
`php consumer.php PaymentProcessedEvent`

回滾庫存  
`php consumer.php RollbackInventoryEvent`

取消訂單  
`php consumer.php RollbackOrderEvent`