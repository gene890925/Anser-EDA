<?php

namespace App\Framework;

use ReflectionClass;
use ReflectionMethod;
use App\Framework\EventBus;

/**
 * Class HandlerScanner
 * 用於自動掃描 `Saga` 事件處理類別，並自動註冊標記為 `#[EventHandler]` 的方法。
 * 這樣，當 `Saga` 需要處理某個事件時，我們不需要手動，而是讓程式自動完成這些步驟。
 */
class HandlerScanner
{
    /**
     * 儲存已經註冊過的事件處理器，避免重複註冊
     *
     * @var array<string, array<string, bool>> 
     * 格式：
     * [
     *    'App\Events\OrderCreatedEvent' => ['onOrderCreated' => true],
     *    'App\Events\InventoryDeductedEvent' => ['onInventoryDeducted' => true],
     * ]
     */
    private array $registeredEventHandlers = [];

    /**
     * 掃描 `Saga` 內的所有類別，尋找 `#[EventHandler]` 註解的方法，並註冊到 `EventBus`
     *
     * @param string $namespace 目標掃描的命名空間 (通常是 `App\Sagas`)
     * @param EventBus $eventBus 事件匯流排，用於註冊 `EventHandler`
     * 
     * @return void
     */
    public function scanAndRegisterHandlers(string $namespace, EventBus $eventBus)
    {
        // 取得 `namespace` 下的所有類別
        $classes = $this->getClassesInNamespace($namespace);

        foreach ($classes as $class) {
            #echo "Scanning class: $class\n"; // ✅ Debug 訊息，確認找到的 Saga 類別

            // 確保類別存在，避免因為 autoload 失敗而報錯
            if (!class_exists($class)) {
                echo " [⚠] Class does not exist: $class\n";
                continue;
            }

            // ✅ **初始化該 Saga，並傳入 `EventBus`
            $instance = new $class($eventBus);

            $reflectionClass = new ReflectionClass($class);

            // 掃描該類別內的所有 `public` 方法
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // 檢查該方法是否有使用 `#[EventHandler]` 註解
                foreach ($method->getAttributes() as $attribute) {
                   
                        //echo "✅ Registering Saga EventHandler: $class::{$method->getName()}\n";

                        // 透過 `Reflection` 取得該方法處理的事件類型
                        $eventType = $this->getEventTypeFromMethod($class, $method->getName());

                        // ✅ 確保不重複註冊相同的 `EventHandler`
                        if ($eventType && !isset($this->registeredEventHandlers[$eventType][$method->getName()])) {
                            //echo " [✔] Registered EventHandler for event: $eventType -> $class::{$method->getName()}\n";

                            // 註冊到 `EventBus`
                            $eventBus->registerHandler($eventType, [$instance, $method->getName()]);

                            // 標記此 `EventHandler` 已經註冊，避免重複註冊
                            $this->registeredEventHandlers[$eventType][$method->getName()] = true;
                        }
                    
                }
            }
        }
    }

    /**
     * 根據指定 `namespace` 取得所有類別
     * 
     * @param string $namespace - 目標命名空間 (如 `App\Sagas`)
     * @return array<string> - 找到的類別名稱清單
     */
    private function getClassesInNamespace(string $namespace): array
    {
        // 讓 "App" 命名空間對應到專案的根目錄
        $baseDir = realpath(__DIR__ . '/../');
        $relativePath = str_replace('\\', '/', str_replace('App\\', '', $namespace));
        $directory = $baseDir . '/' . $relativePath;

        //echo "Scanning directory: $directory\n"; // ✅ Debug 訊息，確認目錄位置

        // 確保目錄存在，否則返回空陣列
        if (!is_dir($directory)) {
            echo " [⚠] Directory does not exist: $directory\n";
            return [];
        }

        // 取得該目錄下的所有 `.php` 檔案
        $files = glob($directory . '/*.php');

        // 若該目錄沒有任何 PHP 檔案，則輸出錯誤
        if (!$files) {
            echo " [⚠] No files found in: $directory\n"; 
        }

        $classes = [];
        foreach ($files as $file) {
            $className = $namespace . '\\' . basename($file, '.php');
            #echo "Found class file: $className\n"; // ✅ 確認找到的 Saga 類別
            $classes[] = $className;
        }
        
        return $classes;
    }

    /**
     * 從方法的參數推斷其對應的事件類型
     *
     * @param string $class - 目標類別名稱
     * @param string $method - 方法名稱
     * @return string|null - 返回事件類型的完整名稱 (如 `App\Events\OrderCreatedEvent`)，如果找不到則返回 `null`
     */
    private function getEventTypeFromMethod($class, $method)
    {
        $reflectionMethod = new ReflectionMethod($class, $method);
        $parameters = $reflectionMethod->getParameters();
        
        // 如果該方法只有一個參數，則該參數的類型即為事件類型
        if (count($parameters) === 1) {
            return $parameters[0]->getType()->getName();
        }
        
        return null;
    }
}
