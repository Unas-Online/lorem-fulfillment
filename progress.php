<?php

use App\Services\FulfillmentService;
use App\Services\Storage;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// call updateShops every 120 seconds
while (true) {
    updateShops(__DIR__);
    sleep(120);
}

/**
 * Simulate the progress of fulfillment
 * 
 * @param string $base path for shop data
 */
function updateShops(string $base): void
{
    $dirs = array_values(array_filter(scandir("$base/data"), fn($name) => $name[0] != '.'));
    foreach ($dirs as $dir) {
        error_log("updating shop $dir");
        $storage = new Storage(__DIR__, $dir);
        $fulfillmentService = new FulfillmentService($storage);
        $fulfillmentService->simulateFulfillment();
    }
}
