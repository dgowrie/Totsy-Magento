<?php

class Totsy_Catalog_Model_Mysql4_Product_Purchase_Limit_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
   
    public function _construct(){
        $this->_init('totsy_catalog/product_purchase_limit');
    }
}