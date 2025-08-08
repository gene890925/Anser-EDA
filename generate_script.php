<?php
require_once __DIR__ . '/vendor/autoload.php';

use SDPMlab\AnserEDA\HandlerScanner;

// 使用 HandlerScanner 自動掃描並生成跨平台腳本文件
$handlerScanner = new HandlerScanner();
$sagaFilePath = __DIR__ . '/Sagas/OrderSaga.php';

try {
    // Scan event types and generate cross-platform script files (default in scripts directory)
    $result = $handlerScanner->scanAndGenerateCrossPlatformScripts($sagaFilePath);
    
    // Display results
    $handlerScanner->displayBatchFileResults($result['event_types'], $result);
    
} catch (Exception $e) {
    echo "Failed to generate cross-platform script files: " . $e->getMessage() . "\n";
}