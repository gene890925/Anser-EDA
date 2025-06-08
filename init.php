<?php

require_once './vendor/autoload.php';

use SDPMlab\Anser\Service\ServiceList;

ServiceList::addLocalService(
    name: "ProductionService",
    address: "127.0.0.1",
    port: 8081,
    isHttps: false
);

ServiceList::addLocalService(
    name: "UserService",
    address: "127.0.0.1",
    port: 8080,
    isHttps: false
);

ServiceList::addLocalService(
    name: "OrderService",
    address: "127.0.0.1",
    port: 8082,
    isHttps: false
);

define("LOG_PATH", __DIR__ . DIRECTORY_SEPARATOR ."Logs" . DIRECTORY_SEPARATOR);