<?php

/*
* I'm aware of Harapartners_EmailFactory_Model_Observer::sailthruPurchasing() 
* is ralated to what i'm trying tot do here, but it's not sending updated 
* cart info to sailthru, and thus we have incorrect purchases data (in Sailthru)
*/

class Totsy_Sailthru_Model_Observer
{

    public function updateIncompleteOrder(Varien_Event_Observer $observer)
    {
        $info = $observer->getInfo();
        $cart = $observer->getCart();
        $items = array();

        if (!isset($info) || empty($info) || !is_array($info)) {
            return;
        }

        $allItems = $cart->getQuote()->getAllVisibleItems();
        foreach ($allItems as $ai) {

            $name = $ai->getName();
            $id = $ai->getSku();
            $title = !empty($name)?$name:$id;    
            $qty = $ai->getQty();
            $price = $ai->getProduct()->getFinalPrice()*100;
            $url = $ai->getProduct()->getProductUrl();

            $items[] = compact('id','title','qty','price','url');
        }

        $this->_callPurchaseApi(
            array(
                'email' => Mage::getSingleton('customer/session')->getCustomer()->getEmail(),
                'items' => $items,
                'incomplete' => 1 //0: complete ; 1: imcomplete
            )
        );
    }

    public function removeItemFromIncompleteOrder(Varien_Event_Observer $observer)
    {
        $item = $observer->getItem();
        $cart = $observer->getCart();
        $items = array();
        
        if (!isset($item) || empty($item)) {
            return;
        }

        // mark to remove this item from 
        // sailthru incomplete order
        //$items[] = $this->_preSailthruPurchase(
        //    Mage::getModel('catalog/product')->load($item),
        //    array('qty'=>0)
        //);

        // Add other items from cart to 
        // sailthru incomplete order
        $allItems = $cart->getQuote()->getAllVisibleItems();
        foreach ($allItems as $ai) {

            if ($item == $ai->getId()){
                continue;
            }

            $name = $ai->getName();
            $id = $ai->getSku();
            $title = !empty($name)?$name:$id;    
            $qty = $ai->getQty();
            $price = $ai->getProduct()->getFinalPrice()*100;
            $url = $ai->getProduct()->getProductUrl();

            $items[] = compact('id','title','qty','price','url');
        }

        $this->_callPurchaseApi(array(
            'email' => Mage::getSingleton('customer/session')->getCustomer()->getEmail(), 
            'items' => $items,
            'incomplete' => 1 //0: complete ; 1: imcomplete
        )); 

    }

    protected function _callPurchaseApi($data)
    {
        if (isset($_COOKIE['sailthru_bid'])) {
            $data['message_id'] = $_COOKIE['sailthru_bid'];
        }  
        $queueData = array(
            'call' => array(
                'class' => 'emailfactory/sailthruconfig',
                'methods' => array(
                    'getHandle',
                    'apiPost'
                )
            ),
            'params' => array(
                'apiPost' => array('purchase') + compact('data')
            )
        );
        $queue = Mage::getModel('emailfactory/sailthruqueue');
        $queue->addToQueue($queueData);
    }

    protected function _preSailthruPurchase ($itemInfo, $qty)
    {
        $price = $itemInfo->getSpecialPrice();
        $price = number_format($price, 2);
        $price = $price*100;

        $item = array(
            'id' => $itemInfo->getSku(),
            'url' => $itemInfo->getProductUrl(),
            'title' => $itemInfo->getName(),
            'price' => $price,
            'qty' => $qty['qty']
        );

        return $item;
    }
}