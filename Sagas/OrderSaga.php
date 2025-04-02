<?php
namespace App\Sagas;
require_once __DIR__ . '/../init.php';

use App\Framework\Attributes\EventHandler;
use App\Framework\EventBus;

use App\Events\OrderCreateRequestedEvent;
use App\Events\OrderCreatedEvent;
use App\Events\InventoryDeductedEvent;
use App\Events\PaymentProcessedEvent;
use App\Events\OrderCompletedEvent;

use App\Events\RollbackOrderEvent;
use App\Events\RollbackInventoryEvent;
//use App\Events\RollbackPaymentEvent;

use Services\UserService;
use Services\OrderService;
use Services\ProductionService;
use Services\Models\OrderProductDetail;

class OrderSaga
{
    public EventBus $eventBus;

    public UserService $userService;
    public OrderService $orderService;
    public ProductionService $productionService;

    public $userKey;
    public $orderId;
    public $productList = null;

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    
        $this->userService = new UserService();
        $this->orderService = new OrderService();
        $this->productionService = new ProductionService();
        $this->orderId = $this->generateOrderId();
    }


    #[EventHandler]
    public function onOrderCreateRequested(OrderCreateRequestedEvent $event)
    {
        $userKey ='1';        
        $orderId = $this->generateOrderId();
        echo "📥 Saga Step 1: 創建訂單，訂單 ID: {$orderId}\n";
        
        //請求取得商品最新價格
        $productList = $event->productList;

        
        foreach ($productList as &$product) {  
            $productInfoPrice = $this->productionService->productInfoAction((int) $product['p_key'])->do()->getMeaningData()['data']['price'];
            if (!isset($productInfoPrice)) {
                echo "[x]無法最新產品價格\n";
                return;
            }else{
                $product['price'] = $productInfoPrice;
            }
         
        }
         
        $this->generateProductList($productList);

        $info =  $this->orderService->createOrderAction($userKey, $orderId, $this->productList)->do()->getMeaningData();   
        if ($info['code'] == '200') {
            echo "[x] 訂單建立成功\n";
        }else{
            echo "[x] 訂單建立失敗\n";
            return;
        }
    
        $this->eventBus->publish(OrderCreatedEvent::class, [
            'orderId' => $orderId,
            'userKey' => $userKey,
            'productList' => $this->productList,
            'total' => $info['total']
        ]);
        
    }



    #[EventHandler]
    public function onOrderCreated(OrderCreatedEvent $event)
    {
        echo "📥 Saga Step 2: 訂單建立，開始扣庫存\n";
    
        $successfulDeductions = []; // 記錄成功扣減的庫存
        $inventoryFailed = false;   // 是否有任何一項扣減失敗
    
        foreach ($event->productList as $product) {
            $info =  $this->productionService->reduceInventory($product['p_key'], $event->orderId, $product['amount'])->do()->getMeaningData();
    
            if ($info['code'] == '200') {
                echo "[x] 成功扣減庫存 ID: {$product['p_key']}\n";
                $successfulDeductions[] = $product;
            } else {
                echo "[x] 庫存不足，無法扣減 ID: {$product['p_key']}\n";
                $inventoryFailed = true;
            }
        }
    
        if ($inventoryFailed) {
            //  **如果有部分庫存不足，發送 `RollbackOrderEvent`**
            $this->eventBus->publish(RollbackInventoryEvent::class, [
                'orderId' => $event->orderId,
                'userKey' => $event->userKey,
                'successfulDeductions' => $successfulDeductions, // 只回滾這些成功扣減的庫存
            ]);
            return; // 停止 Saga，不繼續支付流程
        }
    
        // ✅ **所有庫存扣減成功，發送 `InventoryDeductedEvent`，繼續支付流程**
        $this->eventBus->publish(InventoryDeductedEvent::class, [
            'orderId' => $event->orderId,
            'userKey' => $event->userKey,
            'productList' => $successfulDeductions, // 只傳遞成功扣減的產品
            'total' => $event->total
        ]);
    
    }

    #[EventHandler]
    public function onInventoryDeducted(InventoryDeductedEvent $event)
    {
        echo "📥 Saga Step 3: 開始支付\n";
        $info =  $this->userService->walletChargeAction($event->userKey, $event->orderId,$event->total)->do()->getMeaningData();
        
        if ($info['code'] == '200') {
            echo "[x] 支付成功\n";
        } else {
            echo "[x] 支付失敗，開始回滾\n";
    
                //**支付失敗時，直接回滾庫存，不需要退款**
                $this->eventBus->publish(rollbackInventoryEvent::class, [
                    'orderId' => $event->orderId,
                    'userKey' => $event->userKey,
                    'successfulDeductions' => $event->productList
                ]);
            return;
        }
        
         // **發送 `PaymentProcessedEvent`**
         $this->eventBus->publish(PaymentProcessedEvent::class, [
            'orderId' => $event->orderId,
            'success' => true
        ]);
    }

    #[EventHandler]
    public function onPaymentProcessed(PaymentProcessedEvent $event)
    {
        if ($event->success) {
            echo "✅ Saga Step 4: 訂單完成！\n";
            // ✅ **發送 `OrderCompletedEvent` 到 RabbitMQ**
            $this->eventBus->publish(OrderCompletedEvent::class, [
                'orderId' => $event->orderId,
            
            ]);
        } 
    }


    #[EventHandler]
    public function onRollbackInventory(RollbackInventoryEvent $event)
    {
        echo "❌ RollbackSaga  Step 2: 回滾已扣減庫存\n";

        echo "🔄 回滾庫存，訂單 ID: {$event->orderId}\n";
        
        foreach ($event->successfulDeductions as $product) {
            $info =  $this->productionService->addInventoryCompensateAction($product['p_key'], $event->orderId, $product['amount'])->do()->getMeaningData();

            if ($info['code'] == '200') {
                echo "[x] 成功回滾庫存 ID: {$product['p_key']}\n";
            } else {
                echo "[x] 回滾庫存失敗 ID: {$product['p_key']}\n";
            }
        }

        // 發送 `RollbackOrderEvent`
        $this->eventBus->publish(RollbackOrderEvent::class, [
            'orderId' => $event->orderId,
            'userKey' => $event->userKey
        ]);
    }

    #[EventHandler]
    public function onRollbackOrder(RollbackOrderEvent $event)
    {
        echo "❌ RollbackSaga Step 1: 取消訂單\n";
        $info =  $this->orderService->compensateOrderAction($event->userKey,$event->orderId)->do()->getMeaningData();
        
        if ($info['code'] == '200') {
            echo "✅ 訂單取消成功\n";
        } else {
            echo "❌ 訂單取消失敗\n";
        }
    }

    private function generateProductList($data) {

        $this->productList = array_map(function($product) {
            return new OrderProductDetail(
                p_key: $product['p_key'],
                price: $product['price'],
                amount: $product['amount']
            );
        }, $data);
    }

    public function generateOrderId(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
    
}
