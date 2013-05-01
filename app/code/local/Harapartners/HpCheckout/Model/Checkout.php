<?php
/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license [^]
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 *
 */
class Harapartners_HpCheckout_Model_Checkout
{
    const METHOD_GUEST = 'guest';
    const METHOD_CUSTOMER = 'customer';

    protected $_quote;
    protected $_checkoutSession;
    protected $_customerSession;
    protected $_helper;

    public function __construct() {
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_customerSession = Mage::getSingleton('customer/session');
        $this->_helper = Mage::helper( 'checkout' );
    }

    public function getCheckout() {
        return $this->_checkoutSession;
    }

    public function getCustomerSession() {
        return $this->_customerSession;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function saveBilling( $data )
    {
        if( empty( $data ) ) {
            return array( 'status' => -1, 'message' => $this->_helper->__('Invalid data.') );
        }

        $address = $this->getQuote()->getBillingAddress();
        $addressForm = Mage::getModel( 'customer/form' );
        $addressForm->setFormCode( 'customer_address_edit' )
        ->setEntityType( 'customer_address' )
        ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        $addressForm->setEntity($address);
        $addressData = $addressForm->extractData( $addressForm->prepareRequest( $data ) );

        $addressErrors = $addressForm->validateData( $addressData );
        if( $addressErrors !== true ) {
            return array( 'status' => 1, 'message' => $addressErrors );
        }
        $addressForm->compactData( $addressData );
        foreach( $addressForm->getAttributes() as $attribute ) {
            if (!isset($data[$attribute->getAttributeCode()])) {
                $address->setData($attribute->getAttributeCode(), NULL);
            }
        }

        $address->setData( 'email', $data[ 'email' ] );

        if (($validateRes = $address->validate()) !== true) {
            return array('status' => 1, 'message' => $validateRes);
        }

        $address->implodeStreetAddress();

        if (true !== ($result = $this->_validateCustomerData($data))) {
            return $result;
        }

        //        $this->getQuote()->collectTotals();
        //        $this->getQuote()->save();
        return array( 'status' => 0, 'message' => '' );
    }

    protected function _validateCustomerData( array $data )
    {
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('checkout_register')
        ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        $quote = $this->getQuote();
        if ( $quote->getCustomerId() ) {
            $customer = $quote->getCustomer();
            $customerForm->setEntity($customer);
            $customerData = $quote->getCustomer()->getData();
        } else {
            $customer = Mage::getModel('customer/customer');
            $customerForm->setEntity($customer);
            $customerRequest = $customerForm->prepareRequest( $data );
            $customerData = $customerForm->extractData( $customerRequest );
        }

        $customerErrors = $customerForm->validateData( $customerData );
        if ( $customerErrors !== true ) {
            return array(
                'status'     => -1,
                'message'   => implode(', ', $customerErrors)
            );
        }

        if ( $quote->getCustomerId() ) {
            return true;
        }

        $customerForm->compactData($customerData);

        $password = $customer->generatePassword();
        $customer->setPassword($password);
        $customer->setConfirmation($password);

        $result = $customer->validate();
        if (true !== $result && is_array($result)) {
            return array(
                'status'   => -1,
                'message' => implode(', ', $result)
            );
        }

        $quote->getBillingAddress()->setEmail($customer->getEmail());
        Mage::helper('core')->copyFieldset('customer_account', 'to_quote', $customer, $quote);
        return true;
    }

    public function saveShipping( $data )
    {
        if (empty($data)) {
            return array('status' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        $address = $this->getQuote()->getShippingAddress();

        $addressForm    = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
        ->setEntityType('customer_address')
        ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        $addressForm->setEntity($address);
        $addressData    = $addressForm->extractData($addressForm->prepareRequest($data));
        $addressErrors  = $addressForm->validateData($addressData);
        if ($addressErrors !== true) {
            return array('status' => 1, 'message' => $addressErrors);
        }
        $addressForm->compactData($addressData);
        foreach ($addressForm->getAttributes() as $attribute) {
            if (!isset($data[$attribute->getAttributeCode()])) {
                $address->setData($attribute->getAttributeCode(), NULL);
            }
        }

        $address->setSameAsBilling(0);

        $address->implodeStreetAddress();
        $address->setCollectShippingRates(true);

        if (($validateRes = $address->validate())!==true) {
            return array('status' => 1, 'message' => $validateRes);
        }

        //        $this->getQuote()->collectTotals()->save();

        return array( 'status' => 0, 'message' => '' );
    }

    public function saveShippingMethod( $shippingMethod )
    {
        if (empty($shippingMethod)) {
            return array('status' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('status' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        $this->getQuote()->getShippingAddress()
        ->setShippingMethod($shippingMethod);

        return array( 'status' => 0, 'message' => '' );
    }

    public function savePayment( $data, $shouldCollectTotal = true, $withValidate = true )
    {
        if (empty($data)) {
            return array('status' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        $quote = $this->getQuote();
        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        } else {
            $quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        }

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }


        $payment = $quote->getPayment();
        $payment->importData($data, $shouldCollectTotal, $withValidate);

        return array( 'status' => 0, 'message' => '' );
    }


    /**
     * @return Harapartners_HpCheckout_Model_Checkout
     */
    public function saveOrder()
    {
        $this->validate();

        switch ($this->getCheckoutMethod()) {
        case self::METHOD_GUEST:
            $this->_prepareGuestQuote();
            break;
        default:
            $this->_prepareCustomerQuote();
            break;
        }

        $shippingAddresses = $this->getQuote()->getAllShippingAddresses();

    	if ($this->getQuote()->hasVirtualItems()) {
            $shippingAddresses[] = $this->getQuote()->getBillingAddress();
        }

        $fulfillmentTypes = array();

        foreach ( $this->getQuote()->getAllItems() as $item) {
            if($item->getParentItemId()) {
                continue;
            }
            if($item->getIsVirtual()) {
                continue;
            }
            $fulfillmentTypes [$item->getProduct()->getFulfillmentType ()] [] = $item->getId ();
		}

        if(!Mage::getSingleton('checkout/session')->getSplitCartFlag() && array_key_exists('dotcom',$fulfillmentTypes) && array_key_exists('dotcom_stock', $fulfillmentTypes)) {
            $fulfillmentTypes['dotcom'] = array_merge($fulfillmentTypes['dotcom_stock'], $fulfillmentTypes['dotcom']);
            unset($fulfillmentTypes['dotcom_stock']);
        }

        // Sort array to dotcom first, dotcom stock second, and other types third
        if(isset($fulfillmentTypes['dotcom_stock'])) {
            uksort($fulfillmentTypes, array($this, 'sortFulfillmentTypes'));
        }

		if(($this->getQuote()->hasVirtualItems() && !$this->getQuote()->isVirtual()) || count($fulfillmentTypes) > 1) {
            $this->_prepareMultiShip();
			$originalShippingAddress = Mage::getModel('sales/quote_address')
                            ->load($this->getQuote()->getShippingAddress()->getId());
            $originalQuoteAddress = $this->getQuote()->getShippingAddress();
            $skipFirst = true;
			foreach($fulfillmentTypes as $_fulfillmentKey => $_fulfillmentProducts) {
                //skipping the first fulfillment type
	        	if($skipFirst) {
	        		$skipFirst = false;
	        		continue;
	        	}
                $newAddress = clone $originalShippingAddress;
                $this->getQuote()->addShippingAddress($newAddress);
                $newAddress->save();

                //Loop through the default shipping address to remove the items from that shipping address.
                //We are then going to need to add the items to the new shipping address.
	        	foreach($_fulfillmentProducts as $_productId) {
	        		foreach($originalShippingAddress->getItemsCollection() as $addressItem) {
                        $quoteItem = $this->getQuote()->getItemById($addressItem->getQuoteItemId());
                        $qty = $addressItem->getQty();
                        if($quoteItem->getId() == $_productId) {
                            if($addressItem->getHasChildren()) {
                                foreach($addressItem->getChildren() as $child) {
                                    $originalShippingAddress->removeItem($child->getId());
                                    $child->delete();
                                }
                            }
                            $originalShippingAddress->removeItem($addressItem->getId());
                            $addressItem->delete();
                            $newAddress->addItem($quoteItem,$qty);
                        }
	        		}
	        	}

                if($_fulfillmentKey == 'dotcom_stock') {
                    $newAddress->getItemsCollection()->save();
                    $newAddress->setShippingMethod('customshippingrate_customshippingrate');
                    $newAddress->setShippingDescription('Private Label Shipping');
                    $newAddress->setShippingAmount(Mage::getStoreConfig('checkout/cart/split_cart_price'));
                    $newAddress->setBaseShippingAmount(Mage::getStoreConfig('checkout/cart/split_cart_price'),true);
                    $newAddress->setCollectShippingRates(false);
                    $newAddress->getItemsCollection()->save();
                    $newAddress->save();
                } else {
                    $newAddress->getItemsCollection()->save();
                    $newAddress->setShippingMethod($originalShippingAddress->getShippingMethod());
                    $newAddress->setShippingDescription($originalShippingAddress->getShippingDescription());
                    $newAddress->setFreeShipping(true);
                    $newAddress->setShippingAmount(0);
                    $newAddress->setBaseShippingAmount(0);
                    $newAddress->setCollectShippingRates(false);
                    $newAddress->getItemsCollection()->save();
                    $newAddress->save();
                }

	        }
            $originalShippingAddress->save();

            $originalShippingAddress->clearAllItems();
            $originalShippingAddress->getItemsCollection()->clear();

            if(Mage::getSingleton('checkout/session')->getSplitCartFlag()) {
                $originalQuoteAddress->setShippingMethod('customshippingrate_customshippingrate');
                $originalQuoteAddress->setShippingAmount($originalShippingAddress->getShippingAmount() - Mage::getStoreConfig('checkout/cart/split_cart_price'));
                $originalQuoteAddress->setBaseShippingAmount($originalShippingAddress->getBaseShippingAmount() - Mage::getStoreConfig('checkout/cart/split_cart_price'),true);
                $originalQuoteAddress->setCollectShippingRates(false);
                $originalQuoteAddress->save();
            }

            $this->getQuote()->setTotalsCollectedFlag(false);
            $this->getQuote()->collectTotals();
            $this->getQuote()->save();
            $quote = Mage::getModel('sales/quote')->load($this->getQuote()->getId());
            $this->getCheckout()->replaceQuote($quote);
            $this->getQuote()->getPayment()->importData($this->getCheckout()->getData('payment_data'));

        	try {
                Mage::getSingleton('checkout/session')->setCheckoutState(true);
        		Mage::getModel('checkout/type_multishipping')->createOrders();
                $this->_checkoutSession->setLastOrderId(null);
        	}
        	catch (Mage_Core_Exception $e) {
        		Mage::log($e->getMessage());
        		Mage::logException($e);
        	}

        } else {

            $service = Mage::getModel('sales/service_quote', $this->getQuote());
        	$service->submitAll();

            $this->_checkoutSession->setLastQuoteId($this->getQuote()->getId())
                ->setLastSuccessQuoteId($this->getQuote()->getId())
                ->clearHelperData();

            $order = $service->getOrder();

            if ( $order ) {

                //we need this in order to check if an order has virtual items
                //we want to know that for toggling some FAQ copy about virtual products
                if( $this->getQuote()->hasVirtualItems()==1 ) {
                    $order->hasVirtualItems = $this->getQuote()->hasVirtualItems();
                }

                Mage::dispatchEvent('hpcheckout_save_order_after',
                    array('order'=>$order, 'quote'=>$this->getQuote()));

                $redirectUrl = $this->getQuote()->getPayment()->getOrderPlaceRedirectUrl();

                if (!$redirectUrl && $order->getCanSendNewEmailFlag()) {
                    try {
                        $order->sendNewOrderEmail();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }

                $this->_checkoutSession->setLastOrderId($order->getId())
                    ->setRedirectUrl($redirectUrl)
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setOrderIds(null);
            }

            $profiles = $service->getRecurringPaymentProfiles();
            Mage::dispatchEvent(
                'checkout_submit_all_after',
                array('order' => $order, 'quote' => $this->getQuote(), 'recurring_profiles' => $profiles)
            );
            //Fill Customer First/Last Name with Billing Address Infos
            $customer = $this->getCustomerSession()->getCustomer();
            $customer->fillNameWithBillingAddress($order->getBillingAddress());
        }

        Mage::getSingleton('checkout/session')->unsSplitCartFlag();

        return $this;
    }

    public function validate()
    {
        $helper = Mage::helper('checkout');
        $quote  = $this->getQuote();

        if ($quote->getCheckoutMethod() == self::METHOD_GUEST && !$quote->isAllowedGuestCheckout()) {
            Mage::throwException($this->_helper->__('Sorry, guest checkout is not enabled. Please try again or contact store owner.'));
        }
    }

    protected function _prepareGuestQuote()
    {
        $quote = $this->getQuote();
        $quote->setCustomerId(null)
        ->setCustomerEmail($quote->getBillingAddress()->getEmail())
        ->setCustomerIsGuest(true)
        ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        return $this;
    }

    protected function _prepareCustomerQuote()
    {
        $quote      = $this->getQuote();
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $this->getCustomerSession()->getCustomer();
        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $customerBilling = $billing->exportCustomerAddress();
            $customer->addAddress($customerBilling);
            $billing->setCustomerAddress($customerBilling);
        }
        if ($shipping && !$shipping->getSameAsBilling() &&
            (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())) {
            $customerShipping = $shipping->exportCustomerAddress();
            $customer->addAddress($customerShipping);
            $shipping->setCustomerAddress($customerShipping);
        }

        if (isset($customerBilling) && !$customer->getDefaultBilling()) {
            $customerBilling->setIsDefaultBilling(true);
        }
        if ($shipping && isset($customerShipping) && !$customer->getDefaultShipping()) {
            $customerShipping->setIsDefaultShipping(true);
        } else if (isset($customerBilling) && !$customer->getDefaultShipping()) {
                $customerBilling->setIsDefaultShipping(true);
            }
        $quote->setCustomer($customer);
    }

    public function getCheckoutMethod()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return self::METHOD_CUSTOMER;
        }
        if (!$this->getQuote()->getCheckoutMethod()) {
            $this->getQuote()->setCheckoutMethod(self::METHOD_GUEST);
        }
        return $this->getQuote()->getCheckoutMethod();
    }

    protected function _prepareMultiShip()
    {
        $billing = $this->getQuote()->getBillingAddress();
        $shipping = $this->getQuote()->getShippingAddress();
        foreach($shipping->getItemsCollection() as $item) {
            $shipping->removeItem($item->getId());
        }
        foreach($billing->getItemsCollection() as $item) {
            $billing->removeItem($item->getId());
        }
        foreach($this->getQuote()->getAllShippingAddresses() as $_ship) {
            if($_ship->getId() != $shipping->getId()) {
                $this->getQuote()->removeAddress($_ship->getId());
            }
        }

        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            if($item->getProduct()->getIsVirtual()) {
                $billing->addItem($item);
            } else {
                $shipping->addItem($item);
            }
        }
        $this->getQuote()->setIsMultiShipping(1);

        $shipping->save();
        $billing->save();
        $this->getQuote()->save();
    }


    protected function sortFulfillmentTypes($key1, $key2) {

        if(!in_array($key1,array('dotcom_stock','dotcom'))) {
            return -1;
        }
        if($key1 == 'dotcom' && $key2 == 'dotcom_stock') {
            return -1;
        }

        return 1;
    }

}
