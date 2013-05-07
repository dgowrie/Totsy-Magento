<?php

class Totsy_Catalog_Model_Mysql4_Product_Purchase_Limit extends Mage_Core_Model_Mysql4_Abstract
{
   
    protected function _construct(){
        $this->_init('totsy_catalog/product_purchase_limit', 'entity_id');
    }
    
    public function getProductPurchasesByCustomer($product_id,$customer_id){

    	$select = $this->_getReadAdapter()
            ->select()
            ->from($this->getMainTable())
            ->where('product_id = ?', $product_id)
            ->where('customer_id = ?', $customer_id);

        $return = $this->_getReadAdapter()->fetchAll($select);
        if (empty($return)){
            return 0;
        } 
        
        return $return[0]['purchase_count'];
    }


}
