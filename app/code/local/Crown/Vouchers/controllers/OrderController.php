<?php

class Crown_Vouchers_OrderController extends Mage_Core_Controller_Front_Action {
	
	/**
	 * AJAX request to save new order
	 */
	public function createAction() {
		$product_id = $this->getRequest()->getParam('id');
		
		$customer_id = Mage::helper('customer')->getCustomer()->getId();
		
		$association = Mage::getModel('vouchers/association');

        $defaultAddress = Mage::helper('customer')->getCustomer()->getDefaultBillingAddress();

        if(!is_object($defaultAddress)) {
            $this->returnResult(array('error' => "Before you stock up on discounts, we'll need some information from you.  Please fill out your address information in the MY ACCOUNT section"));
            return;
        }
		
		if(!$product_id) {
			$this->returnResult(array('error' => 'Invalid Product ID'));
			return;
		}
		
		if(!$customer_id) {
			$this->returnResult(array('error' => 'Please login first. '));
			return;
		}
		
		if(Mage::helper('vouchers')->hasAssociation($customer_id, $product_id)) {
			$this->returnResult(array('error' => 'You have already received this voucher. '));
			return;
		}
		// Create order
		$order = Mage::getModel('vouchers/order')->createOrder($product_id);

		// Send Voucher Email
		Mage::getModel('vouchers/email')->sendEmail($product_id, $order);

		$this->returnResult(array('success' => 'Your email has been sent!'));
	}

    /**
     * AJAX request to check if voucher is available to user
     *
     * Created by Crown Partners
     */
    public function availableAction() {
        $product_ids = $this->getRequest()->getParam('ids');

        $customer_id = Mage::helper('customer')->getCustomer()->getId();

        if(!$product_ids) {
            return;
        }

        if(!$customer_id) {
            return;
        }

        $product_ids = explode(',',$product_ids);

        $response = array();
        foreach($product_ids as $product_id) {
            if(Mage::helper('vouchers')->hasAssociation($customer_id, $product_id)) {
                $response[] = array('status' => 3, 'id' => $product_id);
            } else {
                $response[] = array('status' => 0, 'id' => $product_id);
            }
        }

        $this->returnResult($response);
    }
	
	private function returnResult($result) {
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}
}