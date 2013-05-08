<?php
/**
 * @category    Totsy
 * @package     Totsy_Sales_Model
 * @author      Tharsan Bhuvanendran <tbhuvanendran@totsy.com>
 * @copyright   Copyright (c) 2012 Totsy LLC
 */

class Totsy_Sales_Model_Observer extends Mage_Sales_Model_Observer
{
    /**
     * Increment the customer purchase count attribute after an order has been
     * placed.
     * Subscribed to the 'sales_order_place_after' event.
     *
     * @param Varien_Event_Observer $observer
     * @return null|int The updated purchase count for the customer.
     */
    public function incrementCustomerPurchaseCount(
        Varien_Event_Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();

        if ($customer = $order->getCustomer()) {
            $customer->setPurchaseCounter($customer->getPurchaseCounter() + 1)
                ->save();

            return $customer->getPurchaseCounter();
        }

        return null;
    }

    /**
     * Decrement the customer purchase count attribute after an order has been
     * placed.
     * Subscribed to the 'order_cancel_after' event.
     *
     * @param Varien_Event_Observer $observer
     * @return null|int The updated purchase count for the customer.
     */
    public function decrementCustomerPurchaseCount(
        Varien_Event_Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();

        // not entirely sure why, but $order-getCustomer() returns NULL here
        if ($customerId = $order->getCustomerId()) {
            $customer =Mage::getModel('customer/customer')->load($customerId);
            $customer->setPurchaseCounter($customer->getPurchaseCounter() - 1)
                ->save();

            return $customer->getPurchaseCounter();
        }

        return null;
    }

    /**
     * Harapartners, Jun
     * Performance optimization, no action during batch import.
     *
     * @param Varien_Event_Observer $observer
     * @return Totsy_Sales_Model_Observer
     */
    public function catalogProductSaveAfter(Varien_Event_Observer $observer) {
        if(!!Mage::registry('is_batch_import_process')){
            return $this;
        }
        $product = $observer->getEvent()->getProduct();
        if ($product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            return $this;
        }
        Mage::getResourceSingleton('sales/quote')->markQuotesRecollect($product->getId());
        return $this;
    }

    /**
     * Method to cancel all orders that have payment failed status and are older than 7 days
     */
    public function cancelOrderWithPaymentFailedAndOlderThan7Days() {
        Mage::log('Starting script to cancel order with payment failed status and older than 7 Days: ',null,'payment_failed_order_cancel.log');
        $limitDate = strtotime("-7 day");
        $count = 0;
        try {
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('status',Harapartners_Fulfillmentfactory_Helper_Data::ORDER_STATUS_PAYMENT_FAILED)
                ->addAttributeToFilter('updated_at', array(
                    'to' => $limitDate,
                    'date' => true
                ));
            foreach($orderCollection as $order) {
                //Get the last fulfillment errorlog message and copy it to the order history
                $lastErrorLog = Mage::getModel('fulfillmentfactory/errorlog')->getCollection()
                                    ->addFieldToFilter('order_id', $order->getId())
                                    ->getLastItem();
                if($lastErrorLog && $lastErrorLog->getId()) {
                    $order->addStatusHistoryComment($lastErrorLog->getMessage());
                }
                $order->cancel()
                      ->save();
                Mage::log('Order Canceled: ' . $order->getId(),null,'payment_failed_order_cancel.log');
                $count++;
                //Delete all fulfillment error logs link to the order
                $errorLogCollection = Mage::getModel('fulfillmentfactory/errorlog')->getCollection()
                    ->addFieldToFilter('order_id', $order->getId());
                foreach($errorLogCollection as $errorLog) {
                    $errorLog->delete();
                }
            }
        } catch(Exception $e) {
            Mage::log('Error: ' . $e->getMessage(),null,'payment_failed_order_cancel.log');
        }
        Mage::log('Success: ' .$count. ' Orders have been canceled.',null,'payment_failed_order_cancel.log');
        return true;
    }

    public function addCategoryInformationToQuoteItem($event) {
        $quoteItems = $event->getItems();

        if(!is_array($quoteItems) || count($quoteItems) <= 0) {
            return $event;
        }

        foreach($quoteItems as $quoteItem) {
            $product = $quoteItem->getProduct();
            if($product->getCategoryId()) {
                $quoteItem->setCategoryId($product->getCategoryId());
                $quoteItem->setCategoryName($product->getCategory()->getName());
            } else {
                $categories = $product->getCategoryCollection();
                foreach($categories as $category) {
                    $category = $category->load($category->getId());
                    if($category->isLiveEvent()) {
                        $quoteItem->setCategoryId($category->getId());
                        $quoteItem->setCategoryName($category->getName());
                        break;
                    }
                }
            }
        }

        return $this;
    }

    public function addEstimatedShipDatetoOrder($observer) {
        $order = $observer->getEvent()->getOrder();

        $estimatedShipDate = Mage::helper('sales/order')->calculateEstimatedShipDate($order);

        if($estimatedShipDate) {
            $order->setEstimatedShipDate($estimatedShipDate);
        }
        return $this;
    }

    public function checkPurchaseLimit($observer){
        
        $data = $observer->getData('info');
        $cart = $observer->getCart();

        foreach ($data as $itemId => $itemInfo) {
            $item = $cart->getQuote()->getItemById($itemId);
            if (!$item 
                || !empty($itemInfo['remove']) 
                || (isset($itemInfo['qty']) && $itemInfo['qty']=='0')
            ) {
                continue;
            }
            $qty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;
            if ($qty <= 0) {
                continue;
            }

            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($item->getProductId());
            if ($product->getId()) {
                Mage::getModel('totsy_catalog/product_purchase_limit')
                    ->checkPurchaseLimit($product,$qty);
            }  
        }

    }
}
