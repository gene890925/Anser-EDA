# Anser: PHP Microservices Orchestration Library

<p align="center">
  <img src="https://i.imgur.com/2vRAcI0.png" alt="logo" width="500" />
</p>

Anser is a PHP-based microservices orchestration library. You can use this library to manage connections and orchestrate your microservices. Through the Anser library, you can easily achieve the following goals:

- Abstract specific classes and implementations for each HTTP-based microservice, and Anser will not limit your communication mode.
- Quickly compose your microservices
- Write a microservices script with order
- Quickly adopt the SAGA Pattern to design your transaction logic
- Simple backup mechanism, transaction restore when service is interrupted

[正體中文文件](README_zh-TW.md)

## Installation

Install Anser library via Composer:

```bash
composer require sdpmlab/anser
```

## Quick Start

### Microservices Connection List

In your project, you must set the microservices connection list during the execution cycle. You can set it through the `ServiceList::addLocalService()` method. You can refer to the example we provide to create your microservices connection list, which will be the basis for all microservices connections.

```php
namespace App\Anser\Config;

use SDPMlab\Anser\Service\ServiceList;

ServiceList::addLocalService("order_service","localhost",8080,false);
ServiceList::addLocalService("product_service","localhost",8081,false);
ServiceList::addLocalService("cart_service","localhost",8082,false);
ServiceList::addLocalService("payment_service","localhost",8083,false);
```

### Abstract Microservice

In Anser, you can abstract all endpoints of a microservice through the `SimpleService` class. We provide an example, you can refer to it to quickly create a microservice class:

```php
namespace App\Anser\Services;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Service\ActionInterface;
use SDPMlab\Anser\Exception\ActionException;
use Psr\Http\Message\ResponseInterface;

class OrderService extends SimpleService
{
    protected $serviceName = "order_service";
    protected $retry      = 1;
    protected $retryDelay = 1;
    protected $timeout    = 10.0;

    /**
     * Get order by order_key
     *
     * @param integer $u_key
     * @param string $order_key
     * @return ActionInterface
     */
    public function getOrder(
        int $u_key,
        string $order_key
    ): ActionInterface {
        $action = $this->getAction("GET", "/api/v2/order/{$order_key}")
            ->addOption("headers", [
                    "X-User-Key" => $u_key
                ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    ActionInterface $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["data"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData([
                        "message" => $e->getMessage()
                    ]);
                }
            );
        return $action;
    }

    /**
     * Create order
     *
     * @param integer $u_key
     * @param integer $p_key
     * @param integer $amount
     * @param integer $price
     * @param string $orch_key
     * @return ActionInterface
     */
    public function createOrder(
        int $u_key,
        int $p_key,
        int $amount,
        int $price,
        string $orch_key
    ): ActionInterface {
        $action = $this->getAction("POST", "/api/v2/order")
            ->addOption("json", [
                "p_key"  => $p_key,
                "price"  => $price,
                "amount" => $amount
            ])
            ->addOption("headers", [
                "X-User-Key" => $u_key,
                "Orch-Key"   => $orch_key
            ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    ActionInterface $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["orderID"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData([
                        "message" => $e->getMessage()
                    ]);
                }
            );
        return $action;
    }

    /**
     * Delete order
     *
     * @param string $order_key
     * @param string $u_key
     * @param string $orch_key
     * @return ActionInterface
     */
    public function deleteOrderByOrchKey(
        string $u_key,
        string $orch_key
    ): ActionInterface {
        $action = $this->getAction('DELETE', "/api/v2/order")
            ->addOption("headers", [
                "X-User-Key" => $u_key,
                "Orch-Key"   => $orch_key
            ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    Action $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["data"]);
                }
            )
            ->failHandler($this->getFailHandler());
        return $action;
    }

    /**
     * Fail handler
     * 
     * @return callable
     */
    protected function getFailHandler(): callable {
        return function (
            ActionException $e
        ) {
            log_message("critical", $e->getMessage());
            $e->getAction()->setMeaningData([
                "message" => $e->getMessage()
            ]);
        };
    }

}
```

You can directly refer to the [`Anser-Action`](https://github.com/SDPM-lab/Anser-Action) library to understand what mechanism Anser provides to handle microservices connections.

### orchestrate Microservices

In Anser, you can orchestrate your microservices through the `Orchestrator` class. We provide an example, you can refer to it to quickly create an orchestration class:

```php
<?php

namespace App\Anser\Orchestrators\V2;

use App\Anser\Sagas\V2\CreateOrderSaga;
use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\V2\ProductService;
use App\Anser\Services\V2\PaymentService;
use App\Anser\Services\V2\OrderService;
use Exception;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class CreateOrderOrchestrator extends Orchestrator
{
    /**
     * The service of product.
     *
     * @var ProductService
     */
    protected ProductService $productService;

    /**
     * The service of payment.
     *
     * @var PaymentService
     */
    protected PaymentService $paymentService;

    /**
     * The service of order.
     *
     * @var OrderService
     */
    protected OrderService $orderService;

    /**
     * The user key of this orchestrator.
     *
     * @var string
     */
    public $user_key = null;

    /**
     * The product information.
     *
     * @var array
     */
    public array $product_data = [];

    /**
     * The order key.
     *
     * @var integer
     */
    public $order_key;

    /**
     * The product key.
     *
     * @var integer
     */
    public $product_key;

    /**
     * The product price * amount.
     *
     * @var int
     */
    public $total = 0;

    /**
     * The product amount.
     *
     * @var integer
     */
    public $product_amount = 0;

    /**
     * The payment key.
     *
     * @var string|null
     */
    public $payment_key = null;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->paymentService = new PaymentService();
        $this->orderService   = new OrderService();
    }

    protected function definition(int $product_key = null, int $product_amount = null, int $user_key = null)
    {
        if (is_null($product_key) || is_null($user_key) || is_null($product_amount)) {
            throw new Exception("The parameters of product or user_key fail.");
        }

        $this->user_key       = $user_key;
        $this->product_amount = $product_amount;
        $this->product_key    = $product_key;

        $this->setServerName("Anser_Server_1");

        // Step 1. Check the product inventory balance.
        $step1 = $this->setStep()->addAction(
            "product_check",
            $this->productService->checkProductInventory($product_key, $product_amount)
        );

        // Step 2. Get product info.
        $step2 = $this->setStep()->addAction(
            "get_product_info",
            $this->productService->getProduct($product_key)
        );

        // Step 3. Check the user wallet balance.
        $step3 = $this->setStep()
        ->addAction(
            "wallet_check",
            // Define the closure of step3.
            static function (
                OrchestratorInterface $runtimeOrch
            ) use (
                $user_key,
                $product_amount
            ) {
                $product_data = $runtimeOrch->getStepAction("get_product_info")->getMeaningData();
                $total        = $product_data["price"] * $product_amount;

                $runtimeOrch->product_data = &$product_data;
                $runtimeOrch->total        = $total;

                $action = $runtimeOrch->paymentService->checkWalletBalance($user_key, $runtimeOrch->total);
                return $action;
            }
        );

        // Start the saga.
        $this->transStart(CreateOrderSaga::class);

        // Step 4. Create order.
        $step4 = $this->setStep()
        ->setCompensationMethod("orderCreateCompensation")
        // Define the closure of step4.
        ->addAction(
            "create_order",
            static function (
                OrchestratorInterface $runtimeOrch
            ) use (
                $user_key,
                $product_amount,
                $product_key
            ) {
                return $runtimeOrch->orderService->createOrder(
                    $user_key,
                    $product_key,
                    $product_amount,
                    $runtimeOrch->product_data["price"],
                    $runtimeOrch->getOrchestratorNumber()
                );
            }
        );

        // Step 5. Create payment.
        $step5 = $this->setStep()
        ->setCompensationMethod("paymentCreateCompensation")
        ->addAction(
            "create_payment",
            // Define the closure of step5.
            static function (
                OrchestratorInterface $runtimeOrch
            ) use (
                $user_key,
                $product_amount
            ) {
                $order_key = $runtimeOrch->getStepAction("create_order")->getMeaningData();

                $runtimeOrch->order_key = $order_key;

                $action = $runtimeOrch->paymentService->createPayment(
                    $user_key,
                    $runtimeOrch->order_key,
                    $product_amount,
                    $runtimeOrch->total,
                    $runtimeOrch->getOrchestratorNumber()
                );

                return $action;
            }
        );

        // Step 6. Reduce the product inventory amount.
        $step6 = $this->setStep()
        ->setCompensationMethod("productInventoryReduceCompensation")
        ->addAction(
            "reduce_product_amount",
            // Define the closure of Step 6.
            static function (
                OrchestratorInterface $runtimeOrch
            ) use ($product_key, $product_amount) {
                $payment_key = $runtimeOrch->getStepAction("create_payment")->getMeaningData();

                $runtimeOrch->payment_key = $payment_key;

                return $runtimeOrch->productService->reduceInventory(
                    $product_key,
                    $product_amount,
                    $runtimeOrch->getOrchestratorNumber()
                );
            }
        );

        // Step 7. Reduce the user wallet balance.
        $step7 = $this->setStep()
        ->setCompensationMethod("walletBalanceReduceCompensation")
        ->addAction(
            "reduce_wallet_balance",
            // Define the closure of step 7.
            static function (
                OrchestratorInterface $runtimeOrch
            ) use ($user_key) {
                return $runtimeOrch->paymentService->reduceWalletBalance(
                    $user_key,
                    $runtimeOrch->total,
                    $runtimeOrch->getOrchestratorNumber()
                );
            }
        );

        $this->transEnd();
    }

    protected function defineResult()
    {
        $data["data"] = [
            "status"    => $this->isSuccess(),
            "order_key" => $this->order_key,
            "product_data" => $this->product_data,
            "total"        => $this->total
        ];

        if (!$this->isSuccess()) {
            $data["data"]["isCompensationSuccess"] = $this->isCompensationSuccess();
        }

        return $data;
    }
}

```

In the above example, we can see that in the `definition` method, we use the `setStep()` method to define the behavior of each step, and use the `addAction()` method to define the logic required for each step.

In `addAction()`, you can pass in two types to achieve different orchestration needs:

1. Pass in the instance of `SDPMlab\Anser\Service\ActionInterface`. When the microservices orchestrator executes this step, it will directly use this `Action` instance to communicate with the microservices.
2. Pass in `callable`. When the microservices orchestrator executes this step, it will execute this Closure and pass in the Runtime Orchestrator. You can get the data of other steps through the Runtime Orchestrator instance to meet more logical requirements, and return the instance of `SDPMlab\Anser\Service\ActionInterface` at the end.

Use the `transStart()` method to start a Saga transaction, and end the Saga transaction in the `transEnd()` method. Then, you can use the `setCompensationMethod()` method to define the compensation behavior of each step. When the step fails, the compensation behavior will be executed automatically.

### Define Compensation

In the above example, we can see that in the `definition` method, we use the `setCompensationMethod()` method to define the compensation behavior of each step. When the step fails, the compensation behavior will be executed automatically.

You must implement the `SDPMlab\Anser\Orchestration\Saga\SimpleSaga` class to define your compensation logic, and get the Runtime Orchestrator instance through the `getOrchestrator()` method in the compensation logic. You can get the data of other steps through the Runtime Orchestrator instance to meet more logical requirements.


```php
namespace App\Anser\Sagas\V2;

use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;
use App\Anser\Services\V2\OrderService;
use App\Anser\Services\V2\ProductService;
use App\Anser\Services\V2\PaymentService;

class CreateOrderSaga extends SimpleSaga
{
    /**
     * The Compensation function for order creating.
     *
     * @return void
     */
    public function orderCreateCompensation()
    {
        $orderService = new OrderService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $user_key  = $this->getOrchestrator()->user_key;

        $orderService->deleteOrderByRuntimeOrch($user_key, $orchestratorNumber)->do();
    }

    /**
     * The Compensation function for product inventory reducing.
     *
     * @return void
     */
    public function productInventoryReduceCompensation()
    {
        $productService = new ProductService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $product_amount     = $this->getOrchestrator()->product_amount;

        // It still need the error condition.
        // It will compensate the product inventory balance Only if the error code is 5XX error.

        $productService->addInventoryByRuntimeOrch($product_amount, $orchestratorNumber)->do();
    }

    /**
     * The Compensation function for user wallet balance reducing.
     *
     * @return void
     */
    public function walletBalanceReduceCompensation()
    {
        $paymentService = new PaymentService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $user_key = $this->getOrchestrator()->user_key;
        $total    = $this->getOrchestrator()->total;

        // It still need the error condition.
        // It will compensate the wallet balance Only if the error code is 5XX error.

        $paymentService->increaseWalletBalance($user_key, $total, $orchestratorNumber)->do();
    }

    /**
     * The Compensation function for payment creating.
     *
     * @return void
     */
    public function paymentCreateCompensation()
    {
        $paymentService = new PaymentService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $payment_key = $this->getOrchestrator()->payment_key;
        $user_key    = $this->getOrchestrator()->user_key;

        $paymentService->deletePaymentByRuntimeOrch($user_key, $orchestratorNumber)->do();
    }
}
```

### Execute the microservices orchestration logic 

Depending on the framework you are using, you will need to execute your Orchestrator somewhere.

Here is a rough example:

```php
use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;

class CreateOrderController extends BaseController
{
    use ResponseTrait;

    public function createOrder()
    {
        $data = $this->request->getJSON(true);

        $product_key    = $data["product_key"];
        $product_amout  = $data["product_amout"];
        $user_key       = $this->request->getHeaderLine("X-User-Key");

        $userOrch = new CreateOrderOrchestrator();

        $result   = $userOrch->build($product_key, $product_amout, $user_key);

        return $this->respond($result);
    }
}
```

We can see that in the `createOrder()` method, we `new CreateOrderOrchestrator();` an Orchestrator instance, and use the `build()` method to start a service collaboration with Saga transaction, and pass in the `product_key`, `product_amout`, `user_key` three parameters in the `build()` method, these parameters will be used in the `definition()` method.

Finally, you will get the return value after the `build()` is completed. This return value comes from the data processed by `defineResult()`.

The above is a full-featured example of the use of Anser Orchestrator Saga. You can use this example to understand how to use Anser Orchestrator.

## License

Anser is released under the MIT License. See the bundled [LICENSE](LICENSE),