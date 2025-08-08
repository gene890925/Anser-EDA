#!/bin/bash

# Switch to project root directory
cd "$(dirname "$0")/.."

echo "Stopping all event listeners..."

if [ -f tmp/pids/OrderCreateRequestedEvent.pid ]; then
    PID=$(cat tmp/pids/OrderCreateRequestedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped OrderCreateRequestedEvent (PID: $PID)"
    fi
    rm -f tmp/pids/OrderCreateRequestedEvent.pid
fi

if [ -f tmp/pids/OrderCreatedEvent.pid ]; then
    PID=$(cat tmp/pids/OrderCreatedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped OrderCreatedEvent (PID: $PID)"
    fi
    rm -f tmp/pids/OrderCreatedEvent.pid
fi

if [ -f tmp/pids/InventoryDeductedEvent.pid ]; then
    PID=$(cat tmp/pids/InventoryDeductedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped InventoryDeductedEvent (PID: $PID)"
    fi
    rm -f tmp/pids/InventoryDeductedEvent.pid
fi

if [ -f tmp/pids/PaymentProcessedEvent.pid ]; then
    PID=$(cat tmp/pids/PaymentProcessedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped PaymentProcessedEvent (PID: $PID)"
    fi
    rm -f tmp/pids/PaymentProcessedEvent.pid
fi

if [ -f tmp/pids/RollbackInventoryEvent.pid ]; then
    PID=$(cat tmp/pids/RollbackInventoryEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped RollbackInventoryEvent (PID: $PID)"
    fi
    rm -f tmp/pids/RollbackInventoryEvent.pid
fi

if [ -f tmp/pids/RollbackOrderEvent.pid ]; then
    PID=$(cat tmp/pids/RollbackOrderEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "Stopped RollbackOrderEvent (PID: $PID)"
    fi
    rm -f tmp/pids/RollbackOrderEvent.pid
fi

echo "All listeners stopped"
