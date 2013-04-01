<?php

class Harapartners_Paymentfactory_Model_Tokenize extends Totsy_Cybersource_Model_Soap
{
    const MINIMUM_AUTHORIZE_AMOUNT      = 1.0;

    protected $_code                    = 'paymentfactory_tokenize';
    protected $_formBlockType           = 'paymentfactory/form';
    protected $_infoBlockType           = 'paymentfactory/info';
    protected $_payment                 = null;
    protected $_canRefundInvoicePartial = true;

    /**
     * Sends Payment Failed email
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    protected function _sendPaymentFailedEmail($payment)
    {
        /* @var $helper Harapartners_Paymentfactory_Helper_Data */
        $helper = Mage::helper('paymentfactory');
        $helper->sendPaymentFailedEmail($payment);

        return;
    }

    // =============================================== //
    // =========== Magento payment work flow ========== //
    // =============================================== //
    public function getConfigPaymentAction(){
        if(!!$this->getData('forced_payment_action')){
            return $this->getData('forced_payment_action');
        }else{
            return 'order';
        }
        // parent::getConfigPaymentAction();
    }

    public function validate(){
        if(!!$this->getData('cybersource_subid')){
                return $this;
            }
        return parent::validate();
    }

    protected function _decryptSubscriptionId($subId){
        try{
            $testSubId = Mage::getModel('core/encryption')->decrypt(base64_decode($subId));
            if(is_numeric($testSubId)){
                $subId = $testSubId;
            }
        }catch (Exception $e){
        }
        return $subId;
    }

    public function order(Varien_Object $payment, $amount){
        //For totsy, no payment is allowed to be captured upon order place
        $customerId = $payment->getOrder()->getQuote()->getCustomerId();
        /* @var $profile Harapartners_Paymentfactory_Model_Profile */
        $profile = Mage::getModel('paymentfactory/profile');
         if (!!$payment->getData('cybersource_subid')){
             //decrypt for the backend
             $subscriptionId = $this->_decryptSubscriptionId($payment->getData('cybersource_subid'));
             if(!!$subscriptionId){
                $payment->setData('cybersource_subid', $subscriptionId);
             }
             $profile->load($subscriptionId,'subscription_id');
         } elseif (!!$payment->getData('cc_number')){
            $profile->loadByCcNumberWithId($payment->getData('cc_number').$customerId.$payment->getCcExpYear().$payment->getCcExpMonth());
            if($profile->getId()){
                $payment->setData('cybersource_subid', $profile->getData('subscription_id'));
            }
         }

        if(!!$profile && !!$profile->getId()
            // $profile->getExpireYear() === $payment->getCcExpYear()
            // $profile->getExpireMonth() === $payment->getCcExpMonth()
        ){
            if(!$profile->getData('saved_by_customer') && ($payment->getOrder()->getQuote()->getData('saved_by_customer') == '1')) {
                $profile->setData('saved_by_customer', 1);
            }
            $profile->setIsDefault(0);
            $profile->save();
             //Checkout with existing profile instead of creating new card
             //$payment->setData('cybersource_subid', $profile->getData('subscription_id'));
             return $this->_validateProfile($profile, $payment);
         }

        return $this->create($payment);
     }

     protected function _validateProfile($profile, $payment){
//         IF VISA, auth 0
//         ELSE auth 1 and void
        if ($profile->getCardType() == 'VI'){
            return $this->authorize($payment, 0.0);
        }elseif ($profile->getCardType() == 'AE'){
            //Amex does not allow Authorization Reversal at this moment
            return $this->authorize($payment, self::MINIMUM_AUTHORIZE_AMOUNT);
        }else{
            $validationStatus = $this->authorize($payment, self::MINIMUM_AUTHORIZE_AMOUNT);
            if($validationStatus){
                $payment->setParentTransactionId($payment->getTransactionId());
                $this->voidSpecial($payment, self::MINIMUM_AUTHORIZE_AMOUNT);
            }
            return $validationStatus;
        }

     }

     protected function iniRequest(){
        parent::iniRequest();
        $this->_addSubscriptionToRequest($this->_payment);

        //Harapartners, Jun, Totsy logic requires Order ID when applicable
        $order = $this->getInfoInstance()->getOrder();
        if(!!$order && !!$order->getData('increment_id')){
            $this->_request->merchantReferenceCode = $order->getData('increment_id');
        }
    }

    public function saveBillingAddress(Varien_Object $payment) {
        $addressCustomer = Mage::getModel('customer/address');
        $customerId = $payment->getOrder()->getQuote()->getCustomerId();
        $billingAddressDatas = $payment->getOrder()->getBillingAddress()->getData();
        unset($billingAddressDatas['entity_id']);
        $addressCustomer->setData($billingAddressDatas)
            ->setCustomerId($customerId)
            ->setIsDefaultBilling(false)
            ->setIsDefaultShipping(false)
            ->save();
        return $addressCustomer->getId();
    }

    // ============================================== //
    // =========== Payment gateway actions ========== //
    // ============================================== //

    //-----------------Create Customer Payment Profile-------//
    public function create(Varien_Object $payment) {
        $error = false;
        $soapClient = $this->getSoapApi();

        parent::iniRequest();

        $paySubscriptionCreateService = new stdClass();
        $paySubscriptionCreateService->run = "true";

        $this->_request->paySubscriptionCreateService = $paySubscriptionCreateService;
        $billingAdressSaved = $payment->getOrder()->getQuote()->getData('billing_selected_by_customer');
        if(!empty($billingAdressSaved)) {
            $addressId = $payment->getOrder()->getQuote()->getData('billing_selected_by_customer');
            $this->addBillingAddress($payment->getOrder()->getBillingAddress(), $payment->getOrder()->getCustomerEmail());
        } else {
            $this->addBillingAddress($payment->getOrder()->getBillingAddress(), $payment->getOrder()->getCustomerEmail());
            $addressId = $this->saveBillingAddress($payment);
            $payment->getOrder()->getQuote()->setData('billing_selected_by_customer',$addressId)->save();
        }
        $this->addCcInfo($payment);

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
        $this->_request->purchaseTotals = $purchaseTotals;

        $subscription = new stdClass();
        $subscription->title  ="On-Demand Profile Test";
        $subscription->paymentMethod = "credit card";
        $this->_request->subscription = $subscription;

        $recurringSubscriptionInfo = new stdClass();
        $recurringSubscriptionInfo->frequency = "on-demand";
        $this->_request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        try {
            $result = $soapClient->runTransaction($this->_request);
            if ($result->reasonCode==self::RESPONSE_CODE_SUCCESS && $result->paySubscriptionCreateReply->reasonCode==self::RESPONSE_CODE_SUCCESS ) {

                $payment->setLastTransId($result->requestID)
                        ->setCcTransId($result->requestID)
                        ->setIsTransactionClosed(0)
                        ->setCybersourceToken($result->requestToken)
                            ->setCcAvsStatus($result->ccAuthReply->avsCode);
                /*
                 * checking if we have cvCode in response bc
                 * if we don't send cvn we don't get cvCode in response
                 */
                if (isset($result->ccAuthReply->cvCode)) {
                    $payment->setCcCidStatus($result->ccAuthReply->cvCode);
                }
            } else {
                 $error = Mage::helper('paymentfactory')->__('There is an error in processing the payment. ' . $this->_errors[$result->reasonCode] . ' Please try again or contact us.');
            }

        } catch (Exception $e) {

            $order = $payment->getOrder();
            $order->setStatus(Harapartners_Fulfillmentfactory_Helper_Data::ORDER_STATUS_PAYMENT_FAILED);
            $this->_sendPaymentFailedEmail($payment);

           Mage::throwException(
                Mage::helper('paymentfactory')->__('Gateway request error: %s', $e->getMessage())
            );
        }

        if ($error !== false) {
            Mage::throwException($error);
        }

        $payment->setCybersourceSubid($result->paySubscriptionCreateReply->subscriptionID);
        try{
            $customerId = $payment->getOrder()->getQuote()->getCustomerId();
            $data = new Varien_Object($payment->getData());
            $data->setData('customer_id', $customerId);
            $data->setData('address_id', $addressId);
            if($payment->getOrder()->getQuote()->getData('saved_by_customer') == '1') {
                $data->setData('saved_by_customer', 1);
            }
            $data->setData('cybersource_sudid', $result->paySubscriptionCreateReply->subscriptionID);
            /* @var $profile Harapartners_Paymentfactory_Model_Profile */
            $profile = Mage::getModel('paymentfactory/profile');
            $profile->importDataWithValidation($data);
            $profile->save();
        }catch (Exception $e) {
            Mage::getModel('core/email_template')->setTemplateSubject('Payment Failed')
                            ->sendTransactional(6, 'support@totsy.com', $payment->getOrder()->getCustomerEmail(), $payment->getOrder()->getCustomer()->getFirstname());

           Mage::throwException(
                Mage::helper('paymentfactory')->__('Can not save payment profile')
            );
        }

        return $this;
    }

    /**
     * Authorizes payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_payment = $payment;
        try {
            parent::authorize($payment, $amount);
        } catch (Exception $e) {
            $order = $payment->getOrder();
            $order->setStatus(Harapartners_Fulfillmentfactory_Helper_Data::ORDER_STATUS_PAYMENT_FAILED);
            if ($amount > self::MINIMUM_AUTHORIZE_AMOUNT) {
                $this->_sendPaymentFailedEmail($payment);
            }
            Mage::throwException(
                Mage::helper('cybersource')->__('Gateway request error: %s', $e->getMessage())
            );
        }

        /* @var $profile Harapartners_Paymentfactory_Model_Profile */
        $profile = Mage::getModel('paymentfactory/profile')
            ->load($payment->getCybersourceSubid(), 'subscription_id');
        $payment->setCcLast4($profile->getData('last4no'));
        $payment->setCcType($profile->getData('card_type'));
        $payment->setCcExpYear($profile->getData('expire_year'));
        $payment->setCcExpMonth($profile->getData('expire_month'));
        $this->_payment = NULL;
        return $this;
    }

    public function void(Varien_Object $payment)
    {
        $this->_payment = $payment;
        parent::void($payment);
        /* @var $profile Harapartners_Paymentfactory_Model_Profile */
        $profile = Mage::getModel('paymentfactory/profile')->load($payment->getCybersourceSubid(),'subscription_id');
        $payment->setCcLast4($profile->getData('last4no'));
        $payment->setCcType($profile->getData('card_type'));
        $payment->setCcExpYear($profile->getData('expire_year'));
        $payment->setCcExpMonth($profile->getData('expire_month'));
        $this->_payment = NULL;
        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $this->_payment = $payment;
        try {
            #Delete Any Auth Transaction linked with the order
            $payment->setParentTransactionId(null)
                    ->save();
            parent::capture($payment, $amount);
        } catch (Exception $e) {
            $order = $payment->getOrder();
            $order->setStatus(Harapartners_Fulfillmentfactory_Helper_Data::ORDER_STATUS_PAYMENT_FAILED)->save();
            $this->_sendPaymentFailedEmail($payment);
            Mage::throwException(
                Mage::helper('cybersource')->__('Gateway request error: %s', $e->getMessage())
            );
        }

        /* @var $profile Harapartners_Paymentfactory_Model_Profile */
        $profile = Mage::getModel('paymentfactory/profile')->load($payment->getCybersourceSubid(),'subscription_id');
        if($profile && $profile->getId()) {
            $payment->setCcLast4($profile->getData('last4no'))
                ->setCcType($profile->getData('card_type'))
                ->setCcExpYear($profile->getData('expire_year'))
                ->setCcExpMonth($profile->getData('expire_month'))
                ->save();
        }
        $this->_payment = NULL;
        return $this;
    }

    /**
     * Tries to authorize $1 for check CC
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return $this
     */
    public function voidSpecial(Varien_Object $payment, $amount)
    {
        $this->_payment = $payment;
        $error = false;
        if ($payment->getTransactionId() && $payment->getCybersourceToken()) {
            $soapClient = $this->getSoapApi();

            parent::iniRequest();

            $ccAuthReversalService = new stdClass();
            $ccAuthReversalService->run = "true";
            $ccAuthReversalService->authRequestID = $payment->getTransactionId();
            $ccAuthReversalService->authRequestToken = $payment->getCybersourceToken();
            $this->_request->ccAuthReversalService = $ccAuthReversalService;

//             $voidService
//            $voidService = new stdClass();
//            $voidService->run = "true";
//            $voidService->authRequestID = $payment->getTransactionId();
//            $voidService->authRequestToken = $payment->getCybersourceToken();
//            $this->_request->voidService = $voidService;

            $purchaseTotals = new stdClass();
            $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
            $purchaseTotals->grandTotalAmount = $amount;
            $this->_request->purchaseTotals = $purchaseTotals;

            try {
                $result = $soapClient->runTransaction($this->_request);
                if ($result->reasonCode==self::RESPONSE_CODE_SUCCESS) {
                    $payment->setTransactionId($result->requestID)
                        ->setCybersourceToken($result->requestToken);
                     //   ->setIsTransactionClosed(1);
                } else {
                     $error = Mage::helper('cybersource')->__('There is an error in processing the payment. ' . $this->_errors[$result->reasonCode] . ' Please try again or contact us.');
                }
            } catch (Exception $e) {
               Mage::throwException(
                    Mage::helper('cybersource')->__('Gateway request error: %s', $e->getMessage())
                );
            }
        }else{
            $error = Mage::helper('cybersource')->__('Invalid transaction id or token');
        }
        if ($error !== false) {
            Mage::throwException($error);
        }
        /* @var $profile Harapartners_Paymentfactory_Model_Profile */
        $profile = Mage::getModel('paymentfactory/profile')->load($payment->getCybersourceSubid(),'subscription_id');
        $payment->setCcLast4($profile->getData('last4no'));
        $payment->setCcType($profile->getData('card_type'));
        $payment->setCcExpYear($profile->getData('expire_year'));
        $payment->setCcExpMonth($profile->getData('expire_month'));
        $this->_payment = NULL;
        return $this;

    }

    // ========================================== //
    // ============= Utilities ================== //
    // ========================================== //

    /**
     * Adds CC info to request
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    protected function addCcInfo($payment){

        if (!!$payment->getData('cybersource_subid')) {
            return;
        } else {
            $card = new stdClass();
            $card->fullName         = $payment->getCcOwner();
            $card->accountNumber    = preg_replace('/[\s\-]/', '', $payment->getCcNumber());
            $card->expirationMonth  = preg_replace('/[\s\-]/', '', $payment->getCcExpMonth());
            $card->expirationYear   = preg_replace('/[\s\-]/', '', $payment->getCcExpYear());
            $card->cardType         = $this->getTypeNumber($payment->getCcType());

            if ($payment->hasCcCid()) {
                $card->cvNumber     = $payment->getCcCid();
            }
            if ($payment->getCcType() == self::CC_CARDTYPE_SS && $payment->hasCcSsIssue()) {
                $card->issueNumber  = $payment->getCcSsIssue();
            }
            if ($payment->getCcType() == self::CC_CARDTYPE_SS && $payment->hasCcSsStartYear()) {
                $card->startMonth   = $payment->getCcSsStartMonth();
                $card->startYear    = $payment->getCcSsStartYear();
            }

            $this->_request->card = $card;
        }
    }

    public function getTypeNumber( $type ) {
        switch ( $type ) {
            case 'VI':
                 return '001';
             case 'AE':
                 return '003';
             case 'MC':
                 return '002';
             case 'DI':
                 return '004';
             default:
                 return 000;
         }
    }

    protected function _addSubscriptionToRequest($payment){
        //For refund we do NOT need subscription info, and $payment will be null
        if(!!$payment){
            $subscription = new stdClass();
            $subscription->title  ="On-Demand Profile Test";
            $subscription->paymentMethod = "credit card";
            $this->_request->subscription = $subscription;

            $recurringSubscriptionInfo = new stdClass();
            $recurringSubscriptionInfo->frequency = "on-demand";
            $recurringSubscriptionInfo->subscriptionID = $payment->getCybersourceSubid();
            $this->_request->recurringSubscriptionInfo = $recurringSubscriptionInfo;
        }
    }

    /**
     * Creates Payment Profile
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Address $billing
     * @param int $customerId
     * @param int $addressId
     * @throws Mage_Core_Exception
     * @return $this
     */
    public function createProfile($payment, $billing, $customerId, $addressId)
    {
        $error      = false;
        $soapClient = $this->getSoapApi();

        parent::iniRequest();

        $paySubscriptionCreateService = new stdClass();
        $paySubscriptionCreateService->run = "true";

        if ($billing->getEmail()) {
            $email = $billing->getEmail();
        } else {
            $email = Mage::getStoreConfig('trans_email/ident_general/email');
        }

        $this->_request->paySubscriptionCreateService = $paySubscriptionCreateService;
        $billTo = new stdClass();
        $billTo->firstName = $billing->getFirstname();
        $billTo->lastName = $billing->getLastname();
        $billTo->company = $billing->getCompany();

        // sanitize the incoming address street data
        $street = $billing->getData('street');
        $billTo->street1 = (is_array($street))
            ? $street[0] . ' ' . $street[1]
            : $street;

        $billTo->city           = $billing->getCity();
        $billTo->state          = $billing->getRegion();
        $billTo->postalCode     = $billing->getPostcode();
        $billTo->country        = 'US';
        $billTo->phoneNumber    = $billing->getTelephone();
        $billTo->email          = $email;
        $billTo->ipAddress      = $this->getIpAddress();

        $this->_request->billTo = $billTo;
        $this->addCcInfo($payment);

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = 'USD';
        $this->_request->purchaseTotals = $purchaseTotals;

        $subscription = new stdClass();
        $subscription->title  ="On-Demand Profile Test";
        $subscription->paymentMethod = "credit card";
        $this->_request->subscription = $subscription;

        $recurringSubscriptionInfo = new stdClass();
        $recurringSubscriptionInfo->frequency = "on-demand";
        $this->_request->recurringSubscriptionInfo = $recurringSubscriptionInfo;
        try {
            $result = $soapClient->runTransaction($this->_request);
            if ($result->reasonCode == self::RESPONSE_CODE_SUCCESS
                && $result->paySubscriptionCreateReply->reasonCode == self::RESPONSE_CODE_SUCCESS)
            {
                $payment->setLastTransId($result->requestID)
                    ->setCcTransId($result->requestID)
                    ->setIsTransactionClosed(0)
                    ->setCybersourceToken($result->requestToken)
                    ->setCcAvsStatus($result->ccAuthReply->avsCode);
                /*
                 * checking if we have cvCode in response bc
                 * if we don't send cvn we don't get cvCode in response
                 */
                if (isset($result->ccAuthReply->cvCode)) {
                    $payment->setCcCidStatus($result->ccAuthReply->cvCode);
                }
            } else {
                $error = Mage::helper('paymentfactory')->__('There is an error in processing the payment. ' . $this->_errors[$result->reasonCode] . ' Please try again or contact us.');
            }
        } catch (Exception $e) {
           Mage::throwException(
                Mage::helper('paymentfactory')->__('Gateway request error: %s', $e->getMessage())
            );
        }

        if ($error !== false) {
            Mage::throwException($error);
        }

        $payment->setCybersourceSubid($result->paySubscriptionCreateReply->subscriptionID);
        try{
            $data = new Varien_Object($payment->getData());
            $data->setData('customer_id', $customerId);
            $data->setData('address_id', $addressId);
            $data->setData('cc_last4', substr($payment->getCcNumber(), -4));
            $data->setData('cybersource_subid', $result->paySubscriptionCreateReply->subscriptionID);
            /* @var $profile Harapartners_Paymentfactory_Model_Profile */
            $profile = Mage::getModel('paymentfactory/profile');
            $profile->loadByCcNumberWithId($payment->getData('cc_number').$customerId.$payment->getCcExpYear().$payment->getCcExpMonth());
            if(!$profile || !$profile->getId()) {
                $profile->importDataWithValidation($data);
                $profile->save();
            } else {
                $profile->setSubscriptionId($data->getData('cybersource_subid'));
                $profile->save();
            }
        }catch (Exception $e) {
            Mage::throwException(
                Mage::helper('paymentfactory')->__('Can not save payment profile : ' . $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * Validate Subscription Id
     *
     * @param Harapartners_Paymentfactory_Model_Profile $profile
     * @return bool
     */
    public function checkProfile(Harapartners_Paymentfactory_Model_Profile $profile)
    {
        if (!$profile->getSubscriptionId()) {
            return false;
        }

        $soapClient = $this->getSoapApi();
        parent::iniRequest();
        $subscription = new stdClass();
        $subscription->subscriptionID = $profile->getSubscriptionId();
        $subscription->recurringSubscriptionInfo = array();
        $this->_request->recurringSubscriptionInfo = $subscription;
        $this->_request->paySubscriptionRetrieveService = new stdClass();
        $this->_request->paySubscriptionRetrieveService->run = 'true';

        try {
            $result = $soapClient->runTransaction($this->_request);
            if ($result->reasonCode == self::RESPONSE_CODE_SUCCESS
                && $result->paySubscriptionRetrieveReply->reasonCode == self::RESPONSE_CODE_SUCCESS) {
                return true;

            }
        } catch (Exception $e) {
        }

        return false;
    }
}
