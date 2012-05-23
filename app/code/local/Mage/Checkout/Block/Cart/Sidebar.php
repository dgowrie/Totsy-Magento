<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Wishlist sidebar block
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Checkout_Block_Cart_Sidebar extends Mage_Checkout_Block_Cart_Abstract
{
    const XML_PATH_CHECKOUT_SIDEBAR_COUNT   = 'checkout/sidebar/count';

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addItemRender('default', 'checkout/cart_item_renderer', 'checkout/cart/sidebar/default.phtml');
    }

    /**
     * Retrieve count of display recently added items
     *
     * @return int
     */
    public function getItemCount()
    {
        $count = $this->getData('item_count');
        if (is_null($count)) {
            $count = Mage::getStoreConfig(self::XML_PATH_CHECKOUT_SIDEBAR_COUNT);
            $this->setData('item_count', $count);
        }
        return $count;
    }

    /**
     * Get array of last added items
     *
     * @return array
     */
    public function getRecentItems($count = null)
    {
        if ($count === null) {
            $count = $this->getItemCount();
        }

        $items = array();
        if (!$this->getSummaryCount()) {
            return $items;
        }

        $i = 0;
        $allItems = array_reverse($this->getItems());
        foreach ($allItems as $item) {
            /* @var $item Mage_Sales_Model_Quote_Item */
            if (!$item->getProduct()->isVisibleInSiteVisibility()) {
                $productId = $item->getProduct()->getId();
                $products  = Mage::getResourceSingleton('catalog/url')
                    ->getRewriteByProductStore(array($productId => $item->getStoreId()));
                if (!isset($products[$productId])) {
                    continue;
                }
                $urlDataObject = new Varien_Object($products[$productId]);
                $item->getProduct()->setUrlDataObject($urlDataObject);
            }

            $items[] = $item;
            if (++$i == $count) {
                break;
            }
        }

        return $items;
    }

    /**
     * Get shopping cart subtotal.
     *
     * It will include tax, if required by config settings.
     *
     * @param   bool $skipTax flag for getting price with tax or not. Ignored in case when we display just subtotal incl.tax
     * @return  decimal
     */
    public function getSubtotal($skipTax = true)
    {
        $subtotal = 0;
        $totals = $this->getTotals();
        $config = Mage::getSingleton('tax/config');
        if (isset($totals['subtotal'])) {
            if ($config->displayCartSubtotalBoth()) {
                if ($skipTax) {
                    $subtotal = $totals['subtotal']->getValueExclTax();
                } else {
                    $subtotal = $totals['subtotal']->getValueInclTax();
                }
            } elseif($config->displayCartSubtotalInclTax()) {
                $subtotal = $totals['subtotal']->getValueInclTax();
            } else {
                $subtotal = $totals['subtotal']->getValue();
                if (!$skipTax && isset($totals['tax'])) {
                    $subtotal+= $totals['tax']->getValue();
                }
            }
        }
        return $subtotal;
    }

    /**
     * Get subtotal, including tax.
     * Will return > 0 only if appropriate config settings are enabled.
     *
     * @return decimal
     */
    public function getSubtotalInclTax()
    {
        if (!Mage::getSingleton('tax/config')->displayCartSubtotalBoth()) {
            return 0;
        }
        return $this->getSubtotal(false);
    }

    /**
     * Add tax to amount
     *
     * @param float $price
     * @param bool $exclShippingTax
     * @return float
     */
    private function _addTax($price, $exclShippingTax=true) {
        $totals = $this->getTotals();
        if (isset($totals['tax'])) {
            if ($exclShippingTax) {
                $price += $totals['tax']->getValue()-$this->_getShippingTaxAmount();
            } else {
                $price += $totals['tax']->getValue();
            }
        }
        return $price;
    }

    /**
     * Get shipping tax amount
     *
     * @return float
     */
    protected function _getShippingTaxAmount()
    {
        $quote = $this->getCustomQuote() ? $this->getCustomQuote() : $this->getQuote();
        return $quote->getShippingAddress()->getShippingTaxAmount();
    }

    /**
     * Get shopping cart items qty based on configuration (summary qty or items qty)
     *
     * @return int | float
     */
    public function getSummaryCount()
    {
        if ($this->getData('summary_qty')) {
            return $this->getData('summary_qty');
        }
        return Mage::getSingleton('checkout/cart')->getSummaryQty();
    }

    /**
     * Get incl/excl tax label
     *
     * @param bool $flag
     * @return string
     */
    public function getIncExcTax($flag)
    {
        $text = Mage::helper('tax')->getIncExcText($flag);
        return $text ? ' ('.$text.')' : '';
    }

    /**
     * Check if one page checkout is available
     *
     * @return bool
     */
    public function isPossibleOnepageCheckout()
    {
        return $this->helper('checkout')->canOnepageCheckout() && !$this->getQuote()->getHasError();
    }

    /**
     * Get one page checkout page url
     *
     * @return bool
     */
    public function getCheckoutUrl()
    {
        return $this->helper('checkout/url')->getCheckoutUrl();
    }

    /**
     * Define if Shopping Cart Sidebar enabled
     *
     * @return bool
     */
    public function getIsNeedToDisplaySideBar()
    {
        return (bool) Mage::app()->getStore()->getConfig('checkout/sidebar/display');
    }

    /**
     * Return customer quote items
     *
     * @return array
     */
    public function getItems()
    {
        if ($this->getCustomQuote()) {
            return $this->getCustomQuote()->getAllVisibleItems();
        }

        return parent::getItems();
    }

    /*
     * Return totals from custom quote if needed
     *
     * @return array
     */
    public function getTotalsCache()
    {
        if (empty($this->_totals)) {
            $quote = $this->getCustomQuote() ? $this->getCustomQuote() : $this->getQuote();
            $this->_totals = $quote->getTotals();
        }
        return $this->_totals;
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();
        $cacheKeyInfo['item_renders'] = $this->_serializeRenders();
        return $cacheKeyInfo;
    }

    /**
     * Serialize renders
     *
     * @return string
     */
    protected function _serializeRenders()
    {
        $result = array();
        foreach ($this->_itemRenders as $type => $renderer) {
            $result[] = implode('|', array($type, $renderer['block'], $renderer['template']));
        }
        return implode('|', $result);
    }

    /**
     * Deserialize renders from string
     *
     * @param string $renders
     * @return Mage_Checkout_Block_Cart_Sidebar
     */
    public function deserializeRenders($renders)
    {
        if (!is_string($renders)) {
            return $this;
        }

        $renders = explode('|', $renders);
        while (!empty($renders)) {
            $template = array_pop($renders);
            $block = array_pop($renders);
            $type = array_pop($renders);
            if (!$template || !$block || !$type) {
                continue;
            }
            $this->addItemRender($type, $block, $template);
        }

        return $this;
    }
    
    //Harapartners, yang, START
    //Add new function for getting estimate shipping date
    public function getShippingDate( $orderConfirmFlag = NULL , $order = NULL ){
        $endDate = 0;

        if ( !!$orderConfirmFlag && !!$order && ( $order instanceof Mage_Sales_Model_Order ) ){
            $items = $order->getItemsCollection();
            if( count($items) ) {
                foreach ( $items as $item){
                    if ($item->getParentItem()) continue;
                    $productId = $item->getProductId();
                    $product = Mage::getModel('catalog/product')->load($productId);
                    $categoryIdsArray = $product->getCategoryIds();
                    foreach ( $categoryIdsArray as $id ){
                        $category = Mage::getModel('catalog/category')->load($id);
                        if (!!$category) {
                            $categoryEndDate = strtotime($category->getData('event_end_date'));
                            $endDate = ( $categoryEndDate > $endDate ) ? $categoryEndDate : $endDate;                        
                        }
                    }
                }
            }            
        }else {
            $items = $this->getItems();
            if( count($items) ) {
                foreach ( $items as $item){
                    $categoryIdsArray = $item->getProduct()->getCategoryIds();
                    foreach ( $categoryIdsArray as $id ){
                        $category = Mage::getModel('catalog/category')->load($id);
                        if (!!$category) {
                            $categoryEndDate = strtotime($category->getData('event_end_date'));
                            $endDate = ( $categoryEndDate > $endDate ) ? $categoryEndDate : $endDate;                        
                        }
                    }
                }
            }
        }

        if ( !$endDate ){
            $endDate = strtotime($order->getCreatedAt());
        }

        for ( $i = 15; $i > 0;  ){
            if ( date("N",$endDate)!=6 && date("N",$endDate)!=7 ){
                $i--;
            }
            $endDate = $endDate + 24*3600;
        }

        for ( $i = 10; $i >0; ){
            if ( date("N",$endDate)!=6 && date("N",$endDate)!=7 ){
                break;
            }else {
                $endDate = $endDate + 24*3600;
            }
        }
        
        return date('m-d-Y', $endDate);
    }
    //Harapartners, yang, END
    
    //Harapartners, yang, START
    //Return total savings
    public function getTotalSaving( $orderConfirmFlag = NULL , $order = NULL ) {       
        $savings = (double)0;       
        if ( !!$orderConfirmFlag && !!$order && ( $order instanceof Mage_Sales_Model_Order ) ){
            $items = $order->getItemsCollection(); 
            if( count($items) ) {          
                   foreach ( $items as $item){
                       if ($item->getParentItem()) continue;
                    $productId = $item->getProductId();
                    $product = Mage::getModel('catalog/product')->load($productId);
                    if (!!$product->getSpecialPrice()) {
                        $priceDiff = (double)$product->getPrice() - (double)$product->getSpecialPrice();
                    }else {
                        $priceDiff = (double)0.00;
                    }                
                    $savings = $savings + $priceDiff * $item->getQtyOrdered();
                }
            }
        }else {
            $items = $this->getItems();
            if( count($items) ) {
                foreach ( $items as $item){
                    $item->getQty();
                    $product = $item->getProduct();
                    if (!!$product->getSpecialPrice()) {
                        $priceDiff = (double)$product->getPrice() - (double)$product->getSpecialPrice();
                    }else {
                        $priceDiff = (double)0.00;
                    }                
                    $savings = $savings + $priceDiff * $item->getQty();    
                }
            } 
        }        
        return $savings;
    }
    //Harapartners, yang, END
}
