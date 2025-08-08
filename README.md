# Anser-EDA Event-Driven Architecture Framework

**Language**: [English](README.md) | [中文](README_zh.md)

Anser-EDA is a hybrid framework that combines orchestration and choreography patterns for event-driven architecture. It helps developers smoothly transition from traditional API architecture to asynchronous event-driven architecture while maintaining existing API structures. The framework integrates Saga patterns with RabbitMQ to implement distributed transactions.

---

## Project Structure

```
Anser-EDA/
│
├── Events/                     # Event Classes
│   ├── InventoryDeductedEvent.php
│   ├── OrderCompletedEvent.php
│   ├── OrderCreateRequestedEvent.php
│   ├── OrderCreatedEvent.php
│   ├── PaymentProcessedEvent.php
│   ├── RollbackInventoryEvent.php
│   └── RollbackOrderEvent.php
│
├── Filters/                    # Event Filters
│   ├── FailHandlerFilter.php
│   └── JsonDoneHandlerFilter.php
│
├── Logs/                       # Application Logs
│
├── Sagas/                      # Saga Process Coordination
│   └── OrderSaga.php
│
├── Services/                   # Microservice Abstractions
│   ├── Models/
│   │   ├── ModifyProduct.php
│   │   └── OrderProductDetail.php
│   ├── OrderService.php
│   ├── ProductionService.php
│   └── UserService.php
│
├── scripts/                    # Auto-generated Scripts (Cross-platform)
│   ├── run_all_events.bat     # Windows startup script
│   ├── run_all_events.sh      # Linux/Mac startup script
│   ├── check_status.sh        # Status checker (Linux/Mac)
│   ├── stop_all.sh            # Stop all listeners (Linux/Mac)
│   └── README.md              # Scripts documentation
│
├── src/                        # Anser Framework Components
│   ├── Attributes/
│   │   └── EventHandler.php
│   ├── EventBus.php
│   ├── EventStore/
│   │   └── EventStoreDB.php
│   ├── HandlerScanner.php     # Auto-scanning & script generation
│   ├── HandlerScannerInterface.php
│   ├── Interfaces/
│   ├── MessageQueue/
│   │   ├── Consumer.php
│   │   ├── MessageBus.php
│   │   └── RabbitMQConnection.php
│   └── Saga.php
│
├── tmp/                        # Temporary Files (auto-created)
│   ├── pids/                   # Process PID files
│   └── logs/                   # Event listener logs
│
├── vendor/                     # Composer Dependencies
│
├── clear_queue.php             # Queue clearing script
├── composer.json               # Composer configuration
├── consumer.php                # Event consumer main program
├── docker-compose.yml          # Docker configuration
├── generate_script.php         # Auto-generate cross-platform scripts
├── initialization.php          # RabbitMQ initialization
├── init.php                    # Application initialization
└── publisher.php               # Event publisher
```

---

## Installation

1. Install Composer dependencies:
   ```bash
   composer install
   ```

2. Start RabbitMQ and EventStore services:
   ```bash
   docker-compose up -d
   ```

3. Initialize RabbitMQ queues:
   ```bash
   php initialization.php
   ```

4. Generate cross-platform scripts:
   ```bash
   php generate_script.php
   ```

---

## Quick Start

### Automatic Script Generation

The framework automatically scans `#[EventHandler]` annotations in your Saga files and generates cross-platform scripts:

```bash
# Generate all scripts automatically
php generate_script.php
```

This will create:
- `scripts/run_all_events.bat` (Windows)
- `scripts/run_all_events.sh` (Linux/Mac)
- `scripts/check_status.sh` (Linux/Mac)
- `scripts/stop_all.sh` (Linux/Mac)

### Running Event Listeners

#### Windows
```cmd
# Run all event listeners (opens separate windows)
scripts\run_all_events.bat
```

#### Linux/Mac
```bash
# Start all listeners in background
./scripts/run_all_events.sh

# Check listener status
./scripts/check_status.sh

# Stop all listeners
./scripts/stop_all.sh

# View logs
tail -f tmp/logs/*.log
```

### Manual Event Consumer Execution

You can also run individual event consumers manually:

```bash
php consumer.php OrderCreateRequestedEvent
php consumer.php OrderCreatedEvent
php consumer.php InventoryDeductedEvent
php consumer.php PaymentProcessedEvent
php consumer.php RollbackInventoryEvent
php consumer.php RollbackOrderEvent
```

---

## Event Flow

The system implements a complete order processing saga with the following events:

1. **OrderCreateRequestedEvent** - Receives order creation request
2. **OrderCreatedEvent** - Order successfully created
3. **InventoryDeductedEvent** - Inventory deduction completed
4. **PaymentProcessedEvent** - Payment processing completed
5. **RollbackInventoryEvent** - Inventory rollback (compensation)
6. **RollbackOrderEvent** - Order cancellation (compensation)

### Saga Pattern Implementation

The `OrderSaga` class orchestrates the entire order process:
- **Success Path**: OrderCreateRequested → OrderCreated → InventoryDeducted → PaymentProcessed → OrderCompleted
- **Failure Path**: Any failure triggers compensation events (RollbackInventory → RollbackOrder)

---

## Key Features

### 1. Auto-Discovery System
- Automatically scans `#[EventHandler]` annotations
- Generates corresponding RabbitMQ queues
- Creates cross-platform management scripts

### 2. Cross-Platform Support
- **Windows**: Batch files with separate CMD windows
- **Linux/Mac**: Shell scripts with background execution and PID management

### 3. Built-in Management Tools
- Process status monitoring
- Centralized log management
- Graceful shutdown capabilities

---

## Development Workflow

1. **Add New Event Handler**:
   ```php
   #[EventHandler]
   public function onNewEvent(NewEvent $event) {
       // Your logic here
   }
   ```

2. **Regenerate Scripts**:
   ```bash
   php generate_script.php
   ```

3. **Restart Listeners**:
   - Windows: Re-run `scripts\run_all_events.bat`
   - Linux/Mac: `./scripts/stop_all.sh && ./scripts/run_all_events.sh`

---

## Architecture Highlights

- **Hybrid Pattern**: Combines orchestration (Saga) with choreography (Event-driven)
- **Event Sourcing**: Integration with EventStore
- **Message Queuing**: RabbitMQ for reliable message delivery
- **Saga Coordination**: Centralized transaction management with compensation
- **Auto-scaling Ready**: Each event type runs in separate processes

---

## Troubleshooting

### View Logs
```bash
# Linux/Mac
tail -f tmp/logs/*.log

# Windows
# Check individual CMD windows or log files in tmp/logs/
```

### Check Process Status
```bash
# Linux/Mac only
./scripts/check_status.sh
```

### Reset Everything
```bash
# Stop all listeners
./scripts/stop_all.sh  # Linux/Mac
# Or close all CMD windows on Windows

# Clear queues
php clear_queue.php
