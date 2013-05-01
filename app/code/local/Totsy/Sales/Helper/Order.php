<?php
/**
 * @category    Totsy
 * @package     Totsy_Sales_Helper
 * @author      Tharsan Bhuvanendran <tbhuvanendran@totsy.com>
 * @copyright   Copyright (c) 2012 Totsy LLC
 */

class Totsy_Sales_Helper_Order
{
    /**
     * The number of days to add to an initial date to calculate estimated
     * shipping dates.
     *
     * @var int
     */
    protected $_shipDateIncrement = 21; // 15 business days

    /**
     * Calculate the estimated ship date for an Order or Quote object, by
     * inspecting each item to find the latest event end date.
     * Then add a fixed number to the result.
     *
     * @param $order Mage_Sales_Model_Quote|Mage_Sales_Model_Order
     * @return int The Unix timestamp of the estimated ship date
     */
    public function calculateEstimatedShipDate($order)
    {
        // use the creation date for the order or quote
        if($order instanceof Mage_Sales_Model_Quote) {
            $shipDate = time();
        } else {
            $shipDate = strtotime($order->getCreatedAt());
        }
        if($order->getRelationParentId()) {
            $parentOrder = Mage::getModel('sales/order')->load($order->getRelationParentId());
            $shipDate = strtotime($parentOrder->getCreatedAt());
            do {
                $parentOrder = Mage::getModel('sales/order')->load($parentOrder->getRelationParentId());
                if($parentOrder->getCreatedAt()) {
                    $shipDate = strtotime($parentOrder->getCreatedAt());
                }
            } while($parentOrder->getRelationParentId());
        }
        // increment the ship date to the end date of the of the event that
        // ends last, in the collection of events
        $items = $order->getItemsCollection();
        $productIds = array();
        foreach ($items as $orderItem) {
            if ($orderItem->getParentItem()) {
                continue;
            }

            $productIds[] = $orderItem->getProductId();
        }

        // the logic of taking "event_end_date" as ship date is not correct,
        // because currently for some categories this date is more than 2020 year so shipping date is looking enourmous.
        // Before optimization this logic doesn't work because this attribute wan't added to collection so it was always empty.
        // I also commented line that responsible for adding attribute so previous logic is keeped,
        // but it need to be reviewed and in case if it's not necessary would be better to remove it at all.
        $categories = Mage::getResourceModel('catalog/category_collection')
            //->addAttributeToSelect('event_end_date')
            ->joinField('product_id',
                'catalog/category_product',
                'product_id',
                'category_id = entity_id',
                null)
            ->addFieldToFilter('product_id', array('in'=>$productIds));

        $categories->getSelect()->group('e.entity_id');

        foreach ($categories as $category) {
            $categoryEndDate = strtotime($category->getEventEndDate());
            $shipDate = max($shipDate, $categoryEndDate);
        }

        $shipDate += 24 * 3600 * $this->_shipDateIncrement;

        // when the calculated ship date falls on a weekend, bump it forward
        // to the following weekday
        if (date('N', $shipDate) > 5) {
            $shipDate += 24 * 3600 * (8 - date('N', $shipDate));
        }

        return $shipDate;
    }

    public function getOrderStatus($order)
    {
        $_virtual_item_count = 0;
        $_total_item_qty = 0;

        $_items = $order->getItemsCollection();

        foreach($_items as $_item) {

            if($_item->is_virtual==1) {
               $_virtual_item_count ++;
            }
            $_total_item_qty += $_item->getQtyOrdered();
        }

        if (($_virtual_item_count == $_total_item_qty) && ($order->getStatus() != 'payment_failed')) {
            return "Emailed";
        } else {
            return $order->getStatusLabel();
        }
     }

    /**
     * Calculate the estimated total savings for an Order or Quote object, by
     * inspecting the produce price difference for each item.
     *
     * @param $order Mage_Sales_Model_Quote|Mage_Sales_Model_Order
     * @return float The estimated total savings.
     */
    public function calculateEstimatedSavings($order)
    {
        $savings = 0;

        $items = $order->getItemsCollection();
        foreach ($items as $orderItem) {
            if ($orderItem->getParentItem()) {
                continue;
            }

            $product = Mage::getModel('catalog/product')->load(
                $orderItem->getProductId()
            );

            $productDiscount = $product->getPrice() - $product->getSpecialPrice();
            if($orderItem->getQty()){
	            $savings += $productDiscount * $orderItem->getQty();
            }
            elseif($orderItem->getQtyOrdered()){
	            $savings += $productDiscount * $orderItem->getQtyOrdered();
            }
            else{
	            $savings += $productDiscount;
            }
        }

        return $savings;
    }

    /**
     * Calculate the estimated total savings for an Quote object
     * This method almost duplicates
     *
     * @param $order Mage_Sales_Model_Quote
     * @return float The estimated total savings.
     */
    public function calculateEstimatedSavingsQuote($quote)
    {
        $savings = 0;

        $items = $quote->getItemsCollection();
        foreach ($items as $quoteItem) {
            if ($quoteItem->getParentItem()) {
                continue;
            }

            $product = $quoteItem->getProduct();
            $productDiscount = $product->getPrice() - $product->getSpecialPrice();
            if($quoteItem->getQty()){
	            $savings += $productDiscount * $quoteItem->getQty();
            }
            elseif($quoteItem->getQtyOrdered()){
	            $savings += $productDiscount * $quoteItem->getQtyOrdered();
            }
            else{
	            $savings += $productDiscount;
            }
        }

        return $savings;
    }
}
