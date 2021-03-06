<?php

require_once __DIR__ . '/../app/Mage.php';
Mage::app();

$options = getopt('o:f', array('order:', 'fulfillment', 'inventory', 'fulfillitems', 'submitorders', 'skus'));

$start = time();

$service = Mage::getModel('fulfillmentfactory/service_dotcom');

if (isset($options['o']) || isset($options['order'])) {
    $orderId = isset($options['o']) ? intval($options['o']) : intval($options['order']);
    if (!$orderId) {
        echo "Invalid Order Identifier: $orderId", PHP_EOL;
        exit(1);
    }

    $order = Mage::getModel('sales/order')->load($orderId);
    $orderArray = array($order);

    if (!$order || !$order->getId()) {
        echo "Invalid Order Identifier: $orderId", PHP_EOL;
        exit(1);
    }

    echo "Submitting Order Number ", $order->getIncrementId(), " for fulfillment ...", PHP_EOL;
    $service->submitOrdersToFulfill($orderArray, true);
}

if (isset($options['f']) || isset($options['fulfillment'])) {
    echo "Processing Fulfillment ...", PHP_EOL;
    $service->fulfillment();
}

if (isset($options['inventory'])) {
    echo "Updating inventory levels ...", PHP_EOL;
    $service->updateInventory();
}

if (isset($options['skus'])) {
    echo "Getting skus available ...", PHP_EOL;
    $service->getAvailableSkus();
}

if (isset($options['fulfillitems'])) {
    echo "Fulfilling order items with available inventory ...", PHP_EOL;
    $service->fulfillInventory();
}

if (isset($options['submitorders'])) {
    echo "Locating and submitting orders for fulfillment ...", PHP_EOL;
    $service->orderFulfillment();
}

$end = time();

$seconds = $end - $start;
$duration = "$seconds seconds";
if ($seconds > 60) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    $duration = "${minutes}m ${seconds}s";
}

echo "The End! Time: $duration, Memory: ", getPeakMemory('MB'), PHP_EOL;

function getPeakMemory($targetUnit = 'kB') {
    $units = array('b', 'kB', 'MB');
    $currentBytes = memory_get_peak_usage();
    $currentUnit  = 'b';

    for ($i = 0; $i < count($units); $i++) {
        if ($units[$i] == $targetUnit) {
            break;
        }

        $currentBytes /= 1024;
        $currentUnit = $units[$i+1];
    }

    return number_format($currentBytes, 1) . ' ' . $currentUnit;
}
