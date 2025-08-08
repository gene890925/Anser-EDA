#!/bin/bash

# Switch to project root directory
cd "$(dirname "$0")/.."

# Create necessary directories
mkdir -p tmp/pids tmp/logs

echo "Starting all event listeners..."

echo "Starting OrderCreateRequestedEvent listener..."
nohup php consumer.php OrderCreateRequestedEvent > tmp/logs/OrderCreateRequestedEvent.log 2>&1 &
echo $! > tmp/pids/OrderCreateRequestedEvent.pid

echo "Starting OrderCreatedEvent listener..."
nohup php consumer.php OrderCreatedEvent > tmp/logs/OrderCreatedEvent.log 2>&1 &
echo $! > tmp/pids/OrderCreatedEvent.pid

echo "Starting InventoryDeductedEvent listener..."
nohup php consumer.php InventoryDeductedEvent > tmp/logs/InventoryDeductedEvent.log 2>&1 &
echo $! > tmp/pids/InventoryDeductedEvent.pid

echo "Starting PaymentProcessedEvent listener..."
nohup php consumer.php PaymentProcessedEvent > tmp/logs/PaymentProcessedEvent.log 2>&1 &
echo $! > tmp/pids/PaymentProcessedEvent.pid

echo "Starting RollbackInventoryEvent listener..."
nohup php consumer.php RollbackInventoryEvent > tmp/logs/RollbackInventoryEvent.log 2>&1 &
echo $! > tmp/pids/RollbackInventoryEvent.pid

echo "Starting RollbackOrderEvent listener..."
nohup php consumer.php RollbackOrderEvent > tmp/logs/RollbackOrderEvent.log 2>&1 &
echo $! > tmp/pids/RollbackOrderEvent.pid

echo "All listeners started"
echo "Check status: ./scripts/check_status.sh"
echo "Stop all listeners: ./scripts/stop_all.sh"
echo "View logs: tail -f tmp/logs/*.log"
