@echo off
cd /d %~dp0

start cmd /k "php consumer.php OrderCreateRequestedEvent"
start cmd /k "php consumer.php OrderCreatedEvent"
start cmd /k "php consumer.php InventoryDeductedEvent"
start cmd /k "php consumer.php PaymentProcessedEvent"
start cmd /k "php consumer.php RollbackInventoryEvent"
start cmd /k "php consumer.php RollbackOrderEvent"

pause 