<?php
/**
 * @category    Totsy
 * @package     Totsy
 * @author      Tharsan Bhuvanendran <tbhuvanendran@totsy.com>
 * @copyright   Copyright (c) 2012 Totsy LLC
 */

$start = time();

require_once __DIR__ . '/../app/Mage.php';
Mage::app();

$dotcom = Mage::helper('fulfillmentfactory/dotcom');

$status = array('fulfillment_failed','processing');

$orders = Mage::getModel('sales/order')->getCollection()
    ->addAttributeToFilter('status', array('in' => $status));

echo "Found ", count($orders), " orders in Fulfillment Failed / Processing status.", PHP_EOL;

$countComplete = 0;
$countSkipped  = 0;
foreach ($orders as $order) {
    $shipments = $order->getShipmentsCollection();

    if (count($shipments)) {
        $order->setData('state', "complete")
              ->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE)
              ->save();
        Mage::helper('fulfillmentfactory/log')
            ->removeErrorLogEntriesForOrder($order);
        echo $order->getIncrementId(), " complete", PHP_EOL;
        $countComplete++;
    } else {
        echo $order->getIncrementId(), " has no shipments", PHP_EOL;
        $countSkipped++;
    }
}

echo "Completed processing ", count($orders), " orders: $countComplete marked as complete, and $countSkipped ignored due to absence of shipments.", PHP_EOL;