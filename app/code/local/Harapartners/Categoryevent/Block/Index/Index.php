<?php
/**
 * Harapartners
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Harapartners License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.Harapartners.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@Harapartners.com so we can send you a copy immediately.
 *
 *
 */

class Harapartners_Categoryevent_Block_Index_Index
    extends Mage_Core_Block_Template
{
    public function getIndexDataObject()
    {
        return Mage::getModel('categoryevent/sortentry')
            ->loadCurrent()
            ->adjustQueuesForCurrentTime();
    }

    /**
     * Get the number of products associated with a category/event.
     *
     * @param int $categoryId
     *
     * @return int
     */
    public function countCategoryProducts($categoryId)
    {
        /** @var $read Varien_Db_Adapter_Interface */
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $select = $read->select()
            ->from(
                array('cp' => 'catalog_category_product'),
                array('product_id', 'category_id')
            )
            ->join(
                array('ccs'=> 'cataloginventory_stock_status'),
                'cp.product_id = ccs.product_id',
                array()
            )
            ->where('ccs.stock_status = 1')
            ->where('cp.category_id = ?', $categoryId)
            ->limit(1);

        return $read->fetchOne($select);
    }

    //adding customer group to the cache key for Plus functionality
    public function getCacheKeyInfo()
    {
        $key = parent::getCacheKeyInfo();
        $key[] = Mage::getSingleton('customer/session')->getCustomerGroupId();
        return $key;
    }
}
