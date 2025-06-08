<?php
namespace App\Sagas;

require_once __DIR__ . '/../init.php';
use SDPMlab\Anser\Service\ConcurrentAction;
use App\Framework\Attributes\EventHandler;
use App\Framework\EventBus;
use App\Framework\Saga;

use App\Events\OrderCreateRequestedEvent;
use App\Events\OrderCreatedEvent;
use App\Events\InventoryDeductedEvent;
use App\Events\PaymentProcessedEvent;
use App\Events\OrderCompletedEvent;
use App\Events\RollbackOrderEvent;
use App\Events\RollbackInventoryEvent;

use Services\UserService;
use Services\OrderService;
use Services\ProductionService;
use Services\Models\OrderProductDetail;

class OrderSaga extends Saga
{

    private UserService $userService;
    private OrderService $orderService;
    private ProductionService $productionService;

    private string $userKey;
    private string $orderId;
    private array $productList = [];

    public function __construct(EventBus $eventBus)
    {
        parent::__construct($eventBus);
        $this->userService = new UserService();
        $this->orderService = new OrderService();
        $this->productionService = new ProductionService();
    }

    #[EventHandler]
    public function onOrderCreateRequested(OrderCreateRequestedEvent $event)
    {
        $productList = $event->productList;
		#獲取最新價格
        foreach ($productList as &$product) {
            $price = $this->productionService->
			productInfoAction((int)$product['p_key'])
			->do()->getMeaningData()['data']['price'] ?? null;
            $product['price'] = $price;
        }
        $this->generateProductList($productList);
		#新增訂單
        $info = $this->orderService->
		createOrderAction($this->userKey, $orderId, $this->productList)
		->do()->getMeaningData();
		#發送下一步消息
        $this->publish(OrderCreatedEvent::class, [
            'orderId' => $orderId,
            'userKey' => $this->userKey,
            'productList' => $this->productList,
            'total' => $info['total']
        ]);
    }

    #[EventHandler]
    public function onOrderCreated(OrderCreatedEvent $event)
    {
        $this->log("📥 Saga Step 2: 訂單建立，開始扣庫存");

        $successfulDeductions = [];
        $inventoryFailed = false;

        $concurrent = new ConcurrentAction();
        $actions = [];

        foreach ($event->productList as $index => $product) {
            $actions["product_{$index}"] = $this->productionService->reduceInventory($product['p_key'], $event->orderId, $product['amount']);
        }

        $concurrent->setActions($actions)->send();
        $results = $concurrent->getActionsMeaningData();

        foreach ($results as $index => $result) {
            $info = $result->getMeaningData();
            if ($this->isSuccess($info)) {
                $successfulDeductions[] = $event->productList[$index];
            } else {
                $inventoryFailed = true;
                break;
            }
        }

        if ($inventoryFailed) {
            $this->compensate(RollbackInventoryEvent::class, [
                'orderId' => $event->orderId,
                'userKey' => $event->userKey,
                'successfulDeductions' => $successfulDeductions,
            ]);
            return;
        }

        $this->publish(InventoryDeductedEvent::class, [
            'orderId' => $event->orderId,
            'userKey' => $event->userKey,
            'productList' => $successfulDeductions,
            'total' => $event->total
        ]);
    }

    #[EventHandler]
    public function onInventoryDeducted(InventoryDeductedEvent $event)
    {
        $this->log("Saga Step 3: 開始支付");
        $info = $this->userService
		->walletChargeAction
		($event->userKey, $event->orderId, $event->total)
		->do()->getMeaningData();
        if (!$this->isSuccess($info)) {
            $this->log("[x] 支付失敗，開始回滾");
			#發送回滾訊行
            $this->compensate(RollbackInventoryEvent::class, [
                'orderId' => $event->orderId,
                'userKey' => $event->userKey,
                'successfulDeductions' => $event->productList
            ]);
            return;
        }
        $this->log("[x] 支付成功");
		#下一步訊息
        $this->publish(PaymentProcessedEvent::class, [
            'orderId' => $event->orderId,
            'success' => true
        ]);
    }

    #[EventHandler]
    public function onPaymentProcessed(PaymentProcessedEvent $event)
    {
        if ($event->success) {
            $this->log("✅ Saga Step 4: 訂單完成！");
            $this->publish(OrderCompletedEvent::class, [
                'orderId' => $event->orderId,
            ]);
        }
    }

    #[EventHandler]
    public function onRollbackInventory(RollbackInventoryEvent $event)
    {
        $this->log("RollbackSaga Step 2: 回滾已扣減庫存");
		#進行回滾
        foreach ($event->successfulDeductions as $product) {
            $info = $this->productionService
			->addInventoryCompensateAction
			($product['p_key'], $event->orderId, $product['amount']
			)->do()->getMeaningData(); 
        }
		#發送下一個訊息
        $this->publish(RollbackOrderEvent::class, [
            'orderId' => $event->orderId,
            'userKey' => $event->userKey
        ]);
    }


    #[EventHandler]
    public function onRollbackOrder(RollbackOrderEvent $event)
    {
        $this->log("❌ RollbackSaga Step 1: 取消訂單");

        $info = $this->orderService
            ->compensateOrderAction($event->userKey, $event->orderId)->do()->getMeaningData();

        if ($this->isSuccess($info)) {
            $this->log("✅ 訂單取消成功");
        } else {
            $this->log("❌ 訂單取消失敗");
        }
    }

    private function generateProductList(array $data): void
    {
        $this->productList = array_map(function ($product) {
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
