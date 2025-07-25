# Anser-EDA 微服務協調編排與編排協調混合框架

本專案使用協助開發者在維持原有 API 架構下導入非同步事件驅動架構（Event-Driven Architecture, EDA）一套可平滑過渡至非同步事件驅動架構(Event-Driven Architecture,EDA)的框架，協助開發者在維持原有 API 架構下導入非同步架構。Anser-EDA框架結合編排協調(Orchestration)與協調編排(Choreography)的混合式協作設計與 Saga 流程，並結合 RabbitMQ ，實現分散式交易。

---

## 目錄結構

```
Anser_EDA/
│
├─ Events/                  # 事件類別
│   ├─ InventoryDeductedEvent.php
│   ├─ OrderCompletedEvent.php
│   ├─ OrderCreateRequestedEvent.php
│   ├─ OrderCreatedEvent.php
│   ├─ PaymentProcessedEvent.php
│   ├─ RollbackInventoryEvent.php
│   └─ RollbackOrderEvent.php
│
├─ Filters/                 # 過濾器
│   ├─ FailHandlerFilter.php
│   └─ JsonDoneHandlerFilter.php
│
├─ Logs/                    # 日誌
│
├─ Sagas/                   # Saga 流程協作
│   └─ OrderSaga.php
│
├─ Services/                # 微服務抽象
│   ├─ Models/
│   │   ├─ ModifyProduct.php
│   │   └─ OrderProductDetail.php
│   ├─ OrderService.php
│   ├─ ProductionService.php
│   └─ UserService.php
│
├─ src/                     # Anser Framework 相關元件
│   ├─ Attributes/
│   │   └─ EventHandler.php
│   ├─ EventBus.php
│   ├─ EventStore/
│   │   └─ EventStoreDB.php
│   ├─ HandlerScanner.php
│   ├─ HandlerScannerInterface.php
│   ├─ Interfaces/
│   ├─ MessageQueue/
│   │   ├─ Consumer.php
│   │   ├─ MessageBus.php
│   │   └─ RabbitMQConnection.php
│   └─ Saga.php
│
├─ vendor/                  # Composer 套件
│
├─ clear_queue.php          # 清除佇列腳本
├─ composer.json            # Composer 設定
├─ composer.lock
├─ consumer.php             # 事件消費主程式
├─ docker-compose.yml
├─ init.php
├─ mqset.php
├─ publisher.php
├─ README.md
├─ run_all_events.bat       # 一鍵啟動所有事件的批次檔
└─ ...
```

---

## 安裝方式

1. 進入容器內，安裝 Composer 套件：
   ```sh
   docker-compose exec app bash
   composer install
   ```
2. 複製 `.env.example` 為 `.env`，並設定 RabbitMQ、EventStore 等連線資訊。

---

## 常用指令

### 1. 執行單一事件消費

在專案根目錄下，執行下列指令可啟動指定事件的消費者：

```sh
php consumer.php OrderCreateRequestedEvent
php consumer.php OrderCreatedEvent
php consumer.php InventoryDeductedEvent
php consumer.php PaymentProcessedEvent
php consumer.php RollbackInventoryEvent
php consumer.php RollbackOrderEvent
```

### 2. 一鍵啟動所有事件（每個事件開一個新視窗）

直接執行批次檔：

```sh
run_all_events.bat
```

---

## 事件流程說明

- **OrderCreateRequestedEvent**：接收訂單建立請求
- **OrderCreatedEvent**：訂單建立完成
- **InventoryDeductedEvent**：庫存扣減完成
- **PaymentProcessedEvent**：扣款完成
- **RollbackInventoryEvent**：回滾庫存
- **RollbackOrderEvent**：取消訂單

---
