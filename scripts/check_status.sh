#!/bin/bash

# Switch to project root directory
cd "$(dirname "$0")/.."

echo "Event Listener Status:"
echo "====================="

if [ -f tmp/pids/OrderCreateRequestedEvent.pid ]; then
    PID=$(cat tmp/pids/OrderCreateRequestedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "[RUNNING] OrderCreateRequestedEvent: Running (PID: $PID)"
    else
        echo "[STOPPED] OrderCreateRequestedEvent: Stopped"
        rm -f tmp/pids/OrderCreateRequestedEvent.pid
    fi
else
    echo "[NOT_STARTED] OrderCreateRequestedEvent: Not started"
fi

if [ -f tmp/pids/OrderCreatedEvent.pid ]; then
    PID=$(cat tmp/pids/OrderCreatedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "[RUNNING] OrderCreatedEvent: Running (PID: $PID)"
    else
        echo "[STOPPED] OrderCreatedEvent: Stopped"
        rm -f tmp/pids/OrderCreatedEvent.pid
    fi
else
    echo "[NOT_STARTED] OrderCreatedEvent: Not started"
fi

if [ -f tmp/pids/InventoryDeductedEvent.pid ]; then
    PID=$(cat tmp/pids/InventoryDeductedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "[RUNNING] InventoryDeductedEvent: Running (PID: $PID)"
    else
        echo "[STOPPED] InventoryDeductedEvent: Stopped"
        rm -f tmp/pids/InventoryDeductedEvent.pid
    fi
else
    echo "[NOT_STARTED] InventoryDeductedEvent: Not started"
fi

if [ -f tmp/pids/PaymentProcessedEvent.pid ]; then
    PID=$(cat tmp/pids/PaymentProcessedEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "[RUNNING] PaymentProcessedEvent: Running (PID: $PID)"
    else
        echo "[STOPPED] PaymentProcessedEvent: Stopped"
        rm -f tmp/pids/PaymentProcessedEvent.pid
    fi
else
    echo "[NOT_STARTED] PaymentProcessedEvent: Not started"
fi

if [ -f tmp/pids/RollbackInventoryEvent.pid ]; then
    PID=$(cat tmp/pids/RollbackInventoryEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "[RUNNING] RollbackInventoryEvent: Running (PID: $PID)"
    else
        echo "[STOPPED] RollbackInventoryEvent: Stopped"
        rm -f tmp/pids/RollbackInventoryEvent.pid
    fi
else
    echo "[NOT_STARTED] RollbackInventoryEvent: Not started"
fi

if [ -f tmp/pids/RollbackOrderEvent.pid ]; then
    PID=$(cat tmp/pids/RollbackOrderEvent.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "[RUNNING] RollbackOrderEvent: Running (PID: $PID)"
    else
        echo "[STOPPED] RollbackOrderEvent: Stopped"
        rm -f tmp/pids/RollbackOrderEvent.pid
    fi
else
    echo "[NOT_STARTED] RollbackOrderEvent: Not started"
fi

