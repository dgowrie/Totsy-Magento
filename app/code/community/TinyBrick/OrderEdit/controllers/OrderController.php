<?php
/**
 * TinyBrick Commercial Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the TinyBrick Commercial Extension License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.delorumcommerce.com/license/commercial-extension
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@tinybrick.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this package to newer
 * versions in the future. 
 *
 * @category   TinyBrick
 * @package    TinyBrick_OrderEdit
 * @copyright  Copyright (c) 2010 TinyBrick Inc. LLC
 * @license    http://store.delorumcommerce.com/license/commercial-extension
 * @Modifications : Tom Royer troyer@totsy.com
 */
class TinyBrick_OrderEdit_OrderController extends Mage_Adminhtml_Controller_Action
{   
    #Only Payment/Billing Informations can be Edited
    public function editAction()
    {
        $order = $this->_initOrder();
        //arrays for restoring order if error is thrown or payment is declined
        $orderArr = $order->getData();
        $billingArr = $order->getBillingAddress()->getData();

        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $order->setCustomer($customer);

        if($order->getShippingAddress()) {
            $shippingArr = $order->getShippingAddress()->getData();
        } else {
            $shippingArr = null;
        }
        try {
            $preTotal = $order->getGrandTotal();
            $edits = array();
            foreach($this->getRequest()->getParams() as $param) {
                if(substr($param,0,1) == '{') {
                if($param = Zend_Json::decode($param)) {
                    $edits[] = $param;
                    }
                }
            }
            $msgs = array();
            
            $changes = array();
            
            $updateBilling = true;
            foreach($edits as $edit) {
                if($edit['type']) {
                    if($edit['type'] == 'payment') {
                        if(array_key_exists('original',$edit) && $edit['original']) {
                            $updateBilling = false;
                        }
                    }
                }
            }
            
            foreach($edits as $edit) {
                if($edit['type']) {
                    if(($edit['type'] == 'billing' && $updateBilling) || $edit['type'] == 'shipping') {
                        $model = Mage::getModel('orderedit/edit_updater_type_'.$edit['type']);
                        if($mess = $model->edit($order,$edit)) {
                            $msgs[] = $mess;
                        } 
                    }
                }
            }
            #After Editing Billing Informations use it to Update Payment Infos
            foreach($edits as $edit) {
                if($edit['type']) {
                    if($edit['type'] == 'payment') {
                        $model = Mage::getModel('orderedit/edit_updater_type_'.$edit['type']);
                        $edit = $this->cleanPaymentData($edit);
                        if($mess = $model->edit($order,$edit)) {
                            $msgs[] = $mess;
                        }
                    }
                }
            }
            //Makes ItemQueues linked with the order Ready to be processed
            //Mage::helper('fulfillmentfactory/data')->makeOrderReadyToBeProcessed($order);
            Mage::getModel('promotionfactory/virtualproductcoupon')->openVirtualProductCouponInOrder($order);

            if(count($msgs) < 1) {
                //auth for more if the total has increased and configured to do so
                $billing  = Mage::getModel('oro_sales/order_billing');
                $billing->invoice($order);
                //fire event and log changes
                Mage::dispatchEvent('orderedit_edit', array('order'=>$order));
                $this->_logChanges($order, $this->getRequest()->getParam('comment'), $this->getRequest()->getParam('admin_user'), $changes);
                echo "Order updated successfully. The page will now refresh.";
            } else {
                echo "There was an error saving information, please try again. : " . $msgs[0];
                $this->_orderRollBack($order, $orderArr, $billingArr, $shippingArr);
            }
        } catch(Exception $e) {
            echo $e->getMessage();
            $this->_orderRollBack($order, $orderArr, $billingArr, $shippingArr);
        }
        return $this;
    }
    
    protected function _orderRollBack($order, $orderArray, $billingArray, $shippingArray)
    {
        $order->setData($orderArray)->save();
        $order->getBillingAddress()->setData($billingArray)->save();
        if($order->getShippingAddress()) {
            $order->getShippingAddress()->setData($shippingArray)->save();
        }
        $order->save();
    }
    
    protected function _logChanges($order, $comment, $user, $array = array()) 
    {
        $logComment = $user . " made changes to this order. <br /><br />";
        foreach($array as $change) {
            if($change != 1) {
                $logComment .= $change;
            }
        }
        $logComment .= "<br />User comment: " . $comment;
        $status = $order->getStatus();
        $notify = 0;
        $order->addStatusToHistory($status, $logComment, $notify);
        $order->save();
    }
    
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('orderedit/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }
    
    public function updateCommentAction()
    {
        if ($order = $this->_initOrder()) {
            echo $this->getLayout()->createBlock('adminhtml/sales_order_view_history')->setTemplate('sales/order/view/history.phtml')->toHtml();
        }
    }
    
    public function recalcAction()
    {
        echo $this->getLayout()->createBlock('orderedit/adminhtml_sales_order_shipping_update')->setTemplate('sales/order/view/tab/shipping-form.phtml')->toHtml();
    }
    
    public function newItemAction()
    {
        echo $this->getLayout()->createBlock('orderedit/adminhtml_sales_order_view_items_add')->setTemplate('sales/order/view/items/add.phtml')->toHtml();
    }
    
    public function getQtyAndDescAction()
    {
        $sku = $this->getRequest()->getParam('sku');

        $product = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('sku', $sku)
            ->getFirstItem();
        $return = array();
        $return['name'] = $product->getName();
        
        if($product->getSpecialPrice()) {
            $return['price'] = round($product->getSpecialPrice(), 2);
        } else {
            $return['price'] = round($product->getPrice(), 2);
        }

        $qty = (int) Mage::getModel('catalog/product')->load($product->getId())->getStockItem()->getQty();
        if($qty>9) {
            $qty = 9;
        }

        $select = "<select class='n-item-qty'>";
        $x = 1;
        while($x <= $qty) {
            $select .= "<option value='" . $x . "'>" . $x . "</option>";
            $x++;
        }
        $select .= "</select>";
        $return['select'] = $select;
        echo Zend_Json::encode($return);
    }

    public function getOrderAddressInformationsAction()
    {
        $addressId = $this->getRequest()->getParam('addressId');
        $address = Mage::getModel('customer/address')->load($addressId);
        $street = explode("\n", $address->getData('street'));
        $address->setData('street1',$street[0]);
        if(array_key_exists(1,$street)) {
            $address->setData('street2',$street[1]);
        }
        echo Zend_Json::encode($address->getData());
    }

    public function checkCouponAvailabilityAction()
    {
        $couponCode = $this->getRequest()->getParam('coupon');
        $coupon = Mage::getModel('salesrule/coupon');
        $coupon->load($couponCode, 'code');
        $result = array('available' => false);
        if($coupon->getId()) {
            if(strtotime($coupon->getExpirationDate()) > time()) {
                $result = array('available' => true);
            }
        }
        echo Zend_Json::encode($result);
    }

    /**
     * Clean Payment datas got from the post to be able to process them
     */
    public function cleanPaymentData($datas) {
        $cleanDatas = null;
        foreach($datas as  $key => $data) {
            $key = str_replace('[','',$key);
            $key = str_replace(']','',$key);
            $key = str_replace('payment','',$key);
            $cleanDatas[$key] = $data;
        }
        return $cleanDatas;
    }
}