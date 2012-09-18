<?php
/**
 * @category    Totsy
 * @package     Harapartners_Customertracking_Helper
 * @author      Tharsan Bhuvanendran <tbhuvanendran@totsy.com>
 * @copyright   Copyright (c) 2012 Totsy LLC
 */
 
class Harapartners_Customertracking_Helper_CustomPixel
{
    const TRIALPAY_SHARED_KEY = '98b57df81d5e93501539700286314bedd73802e25df127db026c196eefef3d8d';

    /**
     * Generate a TrialPay pixel query string.
     * This includes parameters tp_t (Unix timestamp), tp_sid (clickId from user
     * registration), and tp_v1 (HMAC-MD5 signed hash of other parameters).
     *
     * @return string
     */
    public function trialpay()
    {
        $trackingInfo = Mage::getSingleton('customer/session')
            ->getData('affiliate_info');
        $regParams = json_decode($trackingInfo['registration_param'], true);
        $regParams = array_change_key_case($regParams);

        $queryParams = array(
            'tp_t' => time(),
            'tp_sid' => $regParams['subid'],
        );

        $queryParams['tp_v1'] = hash_hmac(
            'md5',
            http_build_query($queryParams),
            self::TRIALPAY_SHARED_KEY
        );

        return http_build_query($queryParams);
    }

    /**
     * Calculate the total commission value for the last order placed, as 50% of
     * the total profit.
     * NOTE: the commission percentage should ideally be a method argument, but
     * the way this method is invoked (via tracking code template variables)
     * makes it awkward.
     *
     * @return string
     */
    public function commision50()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);

        return number_format($order->getProfit() / 2, 2);
    }

    public function linkshare($pixel) {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $trackingInfo = Mage::getSingleton('customer/session')->getData('affiliate_info');
        $regParams = json_decode($trackingInfo['registration_param'], true);
        
        $order = Mage::getModel('sales/order')->load($orderId);

        $html = '';
       // var_dump($orderId);
        if($order->getStatus() == "splitted") {
            $cust_order_id = $order->getIncrementId();
            $split_orders = Mage::getModel('sales/order')->getCollection();
            $split_orders->getSelect()->where('increment_id like "' . $cust_order_id . '-%"');
            foreach($split_orders as $order) {
                $message = Mage::helper('customertracking/linkshare')->linkshareRaw($order, $regParams['siteID'],$customer->getCreatedAt(),$order->getStatus());
                $html .= $pixel . $message . "/> \n";
            }
        } else {
            $message = $this->linkshareRaw($order, $regParams['siteID'],$customer->getCreatedAt(),$order->getStatus());
            $html .= $pixel . $message . "/> <br/>";
        }

        return $html;
    }
}
