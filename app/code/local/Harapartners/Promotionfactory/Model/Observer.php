<?php

/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 *
 */

class Harapartners_Promotionfactory_Model_Observer {

    public function reserveVirtualProductCouponInCart(Varien_Event_Observer $observer) {
        $quoteItem = $observer->getEvent()->getItem();
        $product = $quoteItem->getProduct();

        if(!$product || !$product->getId()){
            return;
        }

        if(!$product->isVirtual()){
            return;
        }
        
        if($product->getIsRecurring()) {
            return;
        }

        if($product->getVoucherCode()) {
            return;
        }

        //Return when there is already a coupon code associated
        $reservationCodeOption = $quoteItem->getOptionByCode('reservation_code');
        if($reservationCodeOption instanceof Mage_Sales_Model_Quote_Item_Option
            && $reservationCodeOption->getValue()){
            return;
        }

        $coupons = Mage::getModel('promotionfactory/virtualproductcoupon')->getCollection()
            ->addFilter('product_id', $product->getId())
            ->addFilter('status', Harapartners_Promotionfactory_Model_Virtualproductcoupon::COUPON_STATUS_AVAILABLE);
        $vpc = $coupons->getFirstItem();

        if($vpc === null){
            return;
        }

        if($vpc instanceof Harapartners_Promotionfactory_Model_Virtualproductcoupon){
            if($vpc->getId()){
                $newOption = Mage::getModel('sales/quote_item_option');
                $newOption->setData(
                    array('code' => 'reservation_code', 'value' => $vpc->getCode())
                );
                $newOption->setCode('reservation_code');
                $newOption->setProduct($product);
                $newOption->setItem($quoteItem);
                $quoteItem->addOption($newOption);
                $vpc->setData('status', Harapartners_Promotionfactory_Model_Virtualproductcoupon::COUPON_STATUS_RESERVED)
                    ->save();
            }else{
                Mage::throwException(sprintf("'%s' does not have any coupon code available.", $product->getName()));
            }
        }
    }

    public function releaseVirtualProductCouponInCart(Varien_Event_Observer $observer) {
        $quoteItem = $observer->getEvent()->getItem();
        $reservationCodeOption = $quoteItem->getOptionByCode('reservation_code');
        if($reservationCodeOption instanceof Mage_Sales_Model_Quote_Item_Option
            && $reservationCodeOption->getId()){
            $vpc = Mage::getModel('promotionfactory/virtualproductcoupon')->load($reservationCodeOption->getValue(), 'code');
            if($vpc instanceof Harapartners_Promotionfactory_Model_Virtualproductcoupon
                && $vpc->getId()
                && $vpc->getStatus() == Harapartners_Promotionfactory_Model_Virtualproductcoupon::COUPON_STATUS_RESERVED){
                $vpc->setStatus(Harapartners_Promotionfactory_Model_Virtualproductcoupon::COUPON_STATUS_AVAILABLE)
                    ->save();
            }
        }
    }

    public function purchaseVirtualProductCouponInOrder(Varien_Event_Observer $observer) {
        $orderItem = $observer->getEvent()->getItem();
        $reservationCodeOption = Mage::getModel('sales/quote_item_option')->getCollection()
            ->addFieldToFilter('item_id', $orderItem->getQuoteItemId())
            ->addFieldToFilter('code', 'reservation_code')
            ->getFirstItem();

        if($reservationCodeOption instanceof Mage_Sales_Model_Quote_Item_Option
            && $reservationCodeOption->getId()){
            $productOptions = unserialize($orderItem->getData('product_options'));
            if(!isset($productOptions['options'])){
                $productOptions['options'] = array();
            }
            $codeExist = false;
            foreach($productOptions['options'] as &$optionDataArray){
                if(isset($optionDataArray['label']) && $optionDataArray['label'] == 'Voucher Code'){
                    $codeExist = true;
                    break;
                }
            }
            if(!$codeExist){
                $productOptions['options'][] = array(
                    'label' => 'Voucher Code',
                    'value' => $reservationCodeOption->getValue() . "\n You will receive an additional email with details on this code"
                );
            }
            $orderItem->setData('product_options', serialize($productOptions));

            $vpc = Mage::getModel('promotionfactory/virtualproductcoupon')->load($reservationCodeOption->getValue(), 'code');
            if($vpc->getId()){
                if($orderItem->getOrderId()) {
                    $order = Mage::getModel('sales/order')->load($orderItem->getOrderId());
                    if($order->getId()) {
                        $vpc->setData('order_id', $order->getId())
                            ->setData('order_increment_id', $order->getIncrementId())
                            ->setData('status', Harapartners_Promotionfactory_Model_Virtualproductcoupon::COUPON_STATUS_PURCHASED)
                            ->save();
                    }
                }
            }else{
                Mage::getSingleton('checkout/session')->addError('There was an error while placing the order with your reserved coupon code.');
            }

        }
    }

    public function cancelVirtualProductCouponInOrder(Varien_Event_Observer $observer) {
        //For order split, DB access is within one transaction, cancelling logic is not required (optional)
        $order = $observer->getEvent()->getOrder();

        if(!$order || !$order->getId()){
            return;
        }
        foreach($order->getAllItems() as $orderItem){
            if($orderItem->getProductType() != 'virtual'){
                continue;
            }
            $productOptions = unserialize($orderItem->getData('product_options'));
            if(isset($productOptions['options'])){
                foreach($productOptions['options'] as $optionDataArray){
                    if(isset($optionDataArray['label'])
                        && $optionDataArray['value']
                        && $optionDataArray['label'] == 'Reservation Code'){
                        $vpc = Mage::getModel('promotionfactory/virtualproductcoupon')->load($optionDataArray['value'], 'code');
                        if($vpc->getId()){
                            $vpc->setData('status', Harapartners_Promotionfactory_Model_Virtualproductcoupon::COUPON_STATUS_AVAILABLE)
                                ->save();
                        }
                    }
                }
            }
        }
        return;
    }

    public function saleOrderPlaceAfter(Varien_Event_Observer $observer) {

        $order = $observer->getEvent()->getOrder();
        $email = $order->getCustomer()->getEmail();

        if(!$order || !$order->getId()) {
            if($order) {
                $order->save();
            }
            if(!$order || !$order->getId()) {
                return;
            }

        }

        $couponCode = $order->getQuote()->getCouponCode();
        if(!$couponCode){
            return;
        }

        $groupCoupon = Mage::getModel('promotionfactory/groupcoupon')->load($couponCode, 'pseudo_code');
        if(!!$groupCoupon && !!$groupCoupon->getId()){
            $groupCoupon->setData('used_count', $groupCoupon->getUsedCount() + 1);
            $groupCoupon->save();
        }

        $customer = $order->getCustomer();
        if(!!$customer && !!$customer->getId()){
            $emailCoupon = Mage::getModel('promotionfactory/emailcoupon')->loadByEmailCouponWithEmail($couponCode, $customer->getEmail());
            if(!!$emailCoupon && !!$emailCoupon->getId()){
                $emailCoupon->setData('used_count', $emailCoupon->getUsedCount() + 1);
                $emailCoupon->save();
            }
        }
    }
}