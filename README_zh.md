# Anser-EDA 事件驅動架構框架

**語言選擇**: [English](README.md) | [中文](README_zh.md)

Anser-EDA 是一個混合式框架，結合編排（Orchestration）與協調（Choreography）模式來實現事件驅動架構。它幫助開發者從傳統的 API 架構平滑過渡到非同步事件驅動架構，同時保持現有的 API 結構。該框架整合了 Saga 模式與 RabbitMQ 來實現分散式事務。

---

## 專案結構

```
Anser-EDA/
│
├── Events/                     # 事件類別
│   ├── InventoryDeductedEvent.php
│   ├── OrderCompletedEvent.php
│   ├── OrderCreateRequestedEvent.php
│   ├── OrderCreatedEvent.php
│   ├── PaymentProcessedEvent.php
│   ├── RollbackInventoryEvent.php
│   └── RollbackOrderEvent.php
│
├── Filters/                    # 事件過濾器
│   ├── FailHandlerFilter.php
│   └── JsonDoneHandlerFilter.php
│
├── Logs/                       # 應用程式日誌
│
├── Sagas/                      # Saga 流程協調
│   └── OrderSaga.php
│
├── Services/                   # 微服務抽象層
│   ├── Models/
│   │   ├── ModifyProduct.php
│   │   └── OrderProductDetail.php
│   ├── OrderService.php
│   ├── ProductionService.php
│   └── UserService.php
│
├── scripts/                    # 自動生成腳本（跨平台）
│   ├── run_all_events.bat     # Windows 啟動腳本
│   ├── run_all_events.sh      # Linux/Mac 啟動腳本
│   ├── check_status.sh        # 狀態檢查器（Linux/Mac）
│   ├── stop_all.sh            # 停止所有監聽器（Linux/Mac）
│   └── README.md              # 腳本文檔
│
├── src/                        # Anser 框架元件
│   ├── Attributes/
│   │   └── EventHandler.php
│   ├── EventBus.php
│   ├── EventStore/
│   │   └── EventStoreDB.php
│   ├── HandlerScanner.php     # 自動掃描與腳本生成
│   ├── HandlerScannerInterface.php
│   ├── Interfaces/
│   ├── MessageQueue/
│   │   ├── Consumer.php
│   │   ├── MessageBus.php
│   │   └── RabbitMQConnection.php
│   └── Saga.php
│
├── tmp/                        # 臨時文件（自動創建）
│   ├── pids/                   # 程序 PID 文件
│   └── logs/                   # 事件監聽器日誌
│
├── vendor/                     # Composer 依賴
│
├── clear_queue.php             # 清除佇列腳本
├── composer.json               # Composer 配置
├── consumer.php                # 事件消費者主程式
├── docker-compose.yml          # Docker 配置
├── generate_script.php         # 自動生成跨平台腳本
├── initialization.php          # RabbitMQ 初始化
├── init.php                    # 應用程式初始化
└── publisher.php               # 事件發布器
```

---

## 安裝

1. 安裝 Composer 依賴：
   ```bash
   composer install
   ```

2. 啟動 RabbitMQ 和 EventStore 服務：
   ```bash
   docker-compose up -d
   ```

3. 初始化 RabbitMQ 佇列：
   ```bash
   php initialization.php
   ```

4. 生成跨平台腳本：
   ```bash
   php generate_script.php
   ```

---

## 快速開始

### 自動腳本生成

框架會自動掃描您的 Saga 文件中的 `#[EventHandler]` 註解並生成跨平台腳本：

```bash
# 自動生成所有腳本
php generate_script.php
```

這將創建：
- `scripts/run_all_events.bat`（Windows）
- `scripts/run_all_events.sh`（Linux/Mac）
- `scripts/check_status.sh`（Linux/Mac）
- `scripts/stop_all.sh`（Linux/Mac）

### 運行事件監聽器

#### Windows
```cmd
# 運行所有事件監聽器（開啟獨立視窗）
scripts\run_all_events.bat
```

#### Linux/Mac
```bash
# 在背景啟動所有監聽器
./scripts/run_all_events.sh

# 檢查監聽器狀態
./scripts/check_status.sh

# 停止所有監聽器
./scripts/stop_all.sh

# 查看日誌
tail -f tmp/logs/*.log
```

### 手動執行事件消費者

您也可以手動運行個別的事件消費者：

```bash
php consumer.php OrderCreateRequestedEvent
php consumer.php OrderCreatedEvent
php consumer.php InventoryDeductedEvent
php consumer.php PaymentProcessedEvent
php consumer.php RollbackInventoryEvent
php consumer.php RollbackOrderEvent
```

---

## 事件流程

系統實現了完整的訂單處理 Saga，包含以下事件：

1. **OrderCreateRequestedEvent** - 接收訂單建立請求
2. **OrderCreatedEvent** - 訂單成功建立
3. **InventoryDeductedEvent** - 庫存扣減完成
4. **PaymentProcessedEvent** - 付款處理完成
5. **RollbackInventoryEvent** - 庫存回滾（補償）
6. **RollbackOrderEvent** - 訂單取消（補償）

### Saga 模式實現

`OrderSaga` 類別協調整個訂單流程：
- **成功路徑**：OrderCreateRequested → OrderCreated → InventoryDeducted → PaymentProcessed → OrderCompleted
- **失敗路徑**：任何失敗都會觸發補償事件（RollbackInventory → RollbackOrder）

---

## 核心功能

### 1. 自動掃描系統
- 自動掃描 `#[EventHandler]` 註解
- 生成對應的 RabbitMQ 佇列
- 創建跨平台管理腳本

### 2. 跨平台支援
- **Windows**：批次檔案，每個監聽器開啟獨立的 CMD 視窗
- **Linux/Mac**：Shell 腳本，背景執行與 PID 管理

### 3. 內建管理工具
- 程序狀態監控
- 集中式日誌管理
- 優雅關閉功能

---

## 開發工作流程

1. **新增事件處理器**：
   ```php
   #[EventHandler]
   public function onNewEvent(NewEvent $event) {
       // 您的邏輯程式碼
   }
   ```

2. **重新生成腳本**：
   ```bash
   php generate_script.php
   ```

3. **重新啟動監聽器**：
   - Windows：重新執行 `scripts\run_all_events.bat`
   - Linux/Mac：`./scripts/stop_all.sh && ./scripts/run_all_events.sh`

---

## 架構特色

- **混合模式**：結合編排（Saga）與協調（事件驅動）
- **事件溯源**：與 EventStore 整合
- **訊息佇列**：RabbitMQ 可靠的訊息傳遞
- **Saga 協調**：集中式事務管理與補償
- **自動擴展就緒**：每個事件類型在獨立程序中運行

---

## 故障排除

### 查看日誌
```bash
# Linux/Mac
tail -f tmp/logs/*.log

# Windows
# 檢查個別 CMD 視窗或 tmp/logs/ 中的日誌文件
```

### 檢查程序狀態
```bash
# 僅限 Linux/Mac
./scripts/check_status.sh
```

### 重置所有設定
```bash
# 停止所有監聽器
./scripts/stop_all.sh  # Linux/Mac
# 或在 Windows 上關閉所有 CMD 視窗

# 清除佇列
php clear_queue.php

# 重新啟動
php initialization.php
php generate_script.php
./scripts/run_all_events.sh  # 或 scripts\run_all_events.bat
```

---

## 運行示例

當系統成功運行時，您會看到類似這樣的輸出：

```
[*] Listening on queue: OrderCreateRequestedEvent
Saga Step 1: 收到訂單建立請求
[x] 訂單建立成功

[*] Listening on queue: OrderCreatedEvent  
Saga Step 2: 訂單建立，開始扣庫存
[x] 扣減庫存成功

[*] Listening on queue: InventoryDeductedEvent
Saga Step 3: 開始支付
[x] 支付成功

[*] Listening on queue: PaymentProcessedEvent
✅ Saga Step 4: 訂單完成！
```

這顯示了完整的 Saga 工作流程在所有事件監聽器中成功執行。

---

## 效能與監控

### 資源使用情況
- **記憶體**：每個消費者通常使用 20-50MB
- **CPU**：閒置時 CPU 使用率低，處理事件時會有峰值
- **網路**：使用 RabbitMQ 的網路頻寬使用量極少

### 監控指令
```bash
# 檢查所有程序（Linux/Mac）
./scripts/check_status.sh

# 即時監控日誌
tail -f tmp/logs/*.log

# 檢查 RabbitMQ 佇列狀態
php clear_queue.php # 顯示佇列資訊
