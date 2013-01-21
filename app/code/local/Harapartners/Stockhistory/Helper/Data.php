<?php

/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 * 
 */

class Harapartners_Stockhistory_Helper_Data extends Mage_Core_Helper_Abstract  {
    
    const ORDER_ITEM_COLLECTION_LOAD_LIMIT = 200;
    
    private $_csvHeader = array(
            'Product ID', 
            'Product Name', 
            'Product SKU', 
            'Size', 
            'Color', 
            'Vendor SKU', 
            'Qty', 
            'Created At', 
            'Updated At', 
            'Status', 
            'Comment'
    );
    
    private $_poProductExport = array(
    		'sku',
    		'type',
    		'vendor_style',
    		'name',
    		'qty',
    		'color',
    		'size',
    		'ages',
    		'departments',
    		'final_sale',
    		'fulfillment_type',
    		'shipping_method',
    		'price',
    		'special_price',
    		'original_wholesale',
    		'sale_wholesale',
    		'image',
    		'small_image',
    		'thumbnail',
    		//'media_gallery',
    		'description',
    		'is_master_pack',
    		'case_pack_qty',
    		'shipping_rate',
    		'shipping_length',
    		'shipping_width',
    		'shipping_height',
    		'gift_wrapping_price',
    		'short_description',	
    		'product_class',
    		'product_subclass',
    		'hot_list',
    		'featured',
    		'weight',
    		'msrp',
    );
    
    public function getCsvHeader(){
        return $this->_csvHeader;
    }
    
    public function getPoProductExportHeader(){
    	return $this->_poProductExport;
    }
    
    public function getGridVendorTypeArray(){
        return array(
                Harapartners_Stockhistory_Model_Vendor::TYPE_VENDOR => 'Vendor', 
                Harapartners_Stockhistory_Model_Vendor::TYPE_SUBVENDOR =>'Sub Vendor', 
                Harapartners_Stockhistory_Model_Vendor::TYPE_DISTRIBUTOR => 'Distributor',
                Harapartners_Stockhistory_Model_Vendor::TYPE_MANUFACTURER => 'Manufacturer'
        );
    }
    
    public function getGridPurchaseorderStatusArray(){
        return array(
                Harapartners_Stockhistory_Model_Purchaseorder::STATUS_OPEN => 'Open',
//                Harapartners_Stockhistory_Model_Purchaseorder::STATUS_ON_HOLD => 'On Hold',
                Harapartners_Stockhistory_Model_Purchaseorder::STATUS_SUBMITTED => 'Submitted',
//                Harapartners_Stockhistory_Model_Purchaseorder::STATUS_COMPLETE => 'Complete',
//                Harapartners_Stockhistory_Model_Purchaseorder::STATUS_CANCELLED => 'Cancelled'
        );
    }
    
    public function getGridTransactionTypeArray(){
        return array(
                Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_AMENDMENT => 'Amendment', 
                Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_EVENT_IMPORT => 'Event Import', 
                Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_DIRECT_IMPORT => 'Direct Import',
                Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_REMOVE => 'Remove Item'
        );
    }
    
    public function getGridTransactionStatusArray(){
        return array(
                Harapartners_Stockhistory_Model_Transaction::STATUS_PENDING => 'Pending', 
                Harapartners_Stockhistory_Model_Transaction::STATUS_PROCESSED => 'Processed', 
                Harapartners_Stockhistory_Model_Transaction::STATUS_FAILED=> 'Failed'
        );
    }
    
    public function getFormAllVendorsArray(){
        $allVendorsArray = array(array('label' => '', 'value' => ''));
        $vendorCollection = Mage::getModel('stockhistory/vendor')->getCollection()->setOrder('vendor_code', 'ASC');
        foreach($vendorCollection as $vendor){
            $allVendorsArray[] = array('label' => $vendor->getVendorCode(), 'value' => $vendor->getVendorCode());
        }
        return $allVendorsArray;
    }
    
    public function getFormVendorTypeArray(){
        return array(
                   array('label' => 'Vendor', 'value' => Harapartners_Stockhistory_Model_Vendor::TYPE_VENDOR),
                   array('label' => 'SubVendor', 'value' => Harapartners_Stockhistory_Model_Vendor::TYPE_SUBVENDOR),
                   array('label' => 'Distributor', 'value' => Harapartners_Stockhistory_Model_Vendor::TYPE_DISTRIBUTOR),
                   array('label' => 'Manufacturer', 'value' => Harapartners_Stockhistory_Model_Vendor::TYPE_MANUFACTURER)
           );
    }
    
    public function getFormVendorStatusArray(){
        return array(
                array('label' => 'Enabled', 'value' => Harapartners_Stockhistory_Model_Vendor::STATUS_ENABLED),
                array('label' => 'Disabled', 'value' => Harapartners_Stockhistory_Model_Vendor::STATUS_DISABLED)
        );
    }
    
    public function getFormPurchaseorderStatusArray(){
        return array(
                array('label' => 'Open', 'value' => Harapartners_Stockhistory_Model_Purchaseorder::STATUS_OPEN),
//                array('label' => 'On Hold', 'value' => Harapartners_Stockhistory_Model_Purchaseorder::STATUS_ON_HOLD),
                array('label' => 'Submitted', 'value' => Harapartners_Stockhistory_Model_Purchaseorder::STATUS_SUBMITTED),
//                array('label' => 'Complete', 'value' => Harapartners_Stockhistory_Model_Purchaseorder::STATUS_COMPLETE),
//                array('label' => 'Cancelled', 'value' => Harapartners_Stockhistory_Model_Purchaseorder::STATUS_CANCELLED)
        );
    }

    
    public function getFormTransactionTypeArray(){
        return array(
                   array('label' => 'Amendment', 'value' => Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_AMENDMENT),
                   array('label' => 'Event Import', 'value' => Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_EVENT_IMPORT),
                   array('label' => 'Direc Import', 'value' => Harapartners_Stockhistory_Model_Transaction::ACTION_TYPE_DIRECT_IMPORT)
           );
    }
    
    public function getFormTransactionStatusArray(){
        return  array(
                array('label' => $this->__('Pending'), 'value' => Harapartners_Stockhistory_Model_Transaction::STATUS_PENDING),
                array('label' => $this->__('Processed'), 'value' => Harapartners_Stockhistory_Model_Transaction::STATUS_PROCESSED ),
                array('label' => $this->__('Failed'), 'value' => Harapartners_Stockhistory_Model_Transaction::STATUS_FAILED)
        );
    }
    
    public function getFormPoArrayByCategoryId($categoryId, $status){
        $poArray = array();
        $poCollection = Mage::getModel('stockhistory/purchaseorder')->getCollection()
                ->loadByCategoryId($categoryId, $status);
        if(!!$poCollection){
            foreach($poCollection as $po){
                $poArray[] = array('label' => $po->getName(), 'value' => $po->getId());
            }
        }
        
        //Create new PO should be the last resort
        $poArray[] = array('label' => 'Create New PO...', 'value' => 0); //0 for new PO
        
        return $poArray;
    }

    public function getFormVendorArrayByCategoryId($categoryId, $status){
        $vendorArray = array();

        if($categoryId){
            $poCollection = Mage::getModel('stockhistory/purchaseorder')->getCollection()
                    ->loadByCategoryId($categoryId, $status);
            if (!!$poCollection) {
                $poCollection->clear();
                $poCollection->getSelect()->group('vendor_id');
                $poCollection->load();
                foreach($poCollection as $po){
                    $vendorArray[] = array('label' => $po->getVendorCode(), 'value' => $po->getVendorId());
                }
            }
        }
        
        return $vendorArray;
    }
    
    public function getProductSoldInfoByCategory($category, $uniqueProductList){
        $productSoldInfoArray = array();
        $uniqueProductIds = array_keys($uniqueProductList);
        
        //Hara Partners, 2012/05/25, Solution for resolving qty_sold problem
        $fromTime = strtotime($category->getData('event_start_date')) - 60*60*24*7;	// 7 days before
        $toTime = strtotime($category->getData('event_end_date')) + 60*60*24*3;	// 3 days after

        $fromDate = date('Y-m-d H:i:s', $fromTime);
        $toDate = date('Y-m-d H:i:s', $toTime);

        $orderItemCollection = Mage::getModel('sales/order_item')->getCollection()
                ->addAttributeToFilter('created_at', array(
                        'from' => $fromDate,
                        'to' => $toDate,
                )
        );
        
        //Products, categories/events, vendors may be deleted, escape query accordingly
        //Note the product id was also checked previously, here is a redundancy check
        $cleanUniqueIds = array();
        foreach(array_unique($uniqueProductIds) as $productIdEntry){
        	if(is_int($productIdEntry) && $productIdEntry > 0){
        		$cleanUniqueIds[] = $productIdEntry;
        	}
        }
        if(count($cleanUniqueIds)){
        	$orderItemCollection->getSelect()->where('product_id IN(' . trim(implode(',', $cleanUniqueIds), ', ') . ')');
        }else{
        	//Force an empty collection for empty product ids
        	$orderItemCollection->getSelect()->where('product_id < 0');
        }
        
        $currentLoadOffset = 0;
        do{
            $tempCollection = clone $orderItemCollection;
            $tempCollection->getSelect()->limit(self::ORDER_ITEM_COLLECTION_LOAD_LIMIT, $currentLoadOffset);
            foreach($tempCollection as $orderItem){
                $productId = $orderItem->getProductId();
                
                //Forever struggle between parent and child
                $itemWithDetailedInfo = Mage::getModel('sales/order_item');
                if(!!$orderItem->getParentItemId()){
                    $itemWithDetailedInfo->load($orderItem->getParentItemId());
                }
                if(!$itemWithDetailedInfo || !$itemWithDetailedInfo->getId()){
                    $itemWithDetailedInfo = $orderItem;
                }
                
                if(!array_key_exists($productId, $productSoldInfoArray)){
                    $productSoldInfoArray[$productId]= array(
                            'total' => 0.0,
                            'qty'    => 0.0,
                    );
                }
                //Note product infor must come from the original $orderItem
                $tempQty = $itemWithDetailedInfo->getQtyOrdered() - $itemWithDetailedInfo->getQtyReturned() - $itemWithDetailedInfo->getQtyCanceled();
                $productSoldInfoArray[$productId]['total'] += $itemWithDetailedInfo->getPrice() * $tempQty;
                $productSoldInfoArray[$productId]['qty'] += $tempQty;
            }
            $currentLoadOffset += self::ORDER_ITEM_COLLECTION_LOAD_LIMIT;
        }while(count($tempCollection) >= self::ORDER_ITEM_COLLECTION_LOAD_LIMIT);

        
        return $productSoldInfoArray;
    }
    
    public function getPoProductExport($poId){
    	//$baseImageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';   
    	$poCollection = Mage::getModel('stockhistory/transaction')->getCollection()->addFieldToFilter('po_id', array('eq' => $poId));
		$csvHeader = $this->getPoProductExportHeader();
		$csv = implode(',', $csvHeader)."\n";
		$uniqueProducts = Array();
		foreach($poCollection as $po){
			$productSku = $po->getProductSku();
			if(in_array($productSku, $uniqueProducts)){
				continue;
			}else{
				$uniqueProducts[] = $productSku;
			}
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $productSku);
			if(!!$product->getId()){
				$productArray = array();
				
				$productArray[] 	=	$productSku;
				$productArray[]		= 	$product->getTypeId();
				$productArray[]	 	= 	$product->getVendorStyle();
				$productArray[]		= 	$product->getName();
				$productArray[] 	=  	Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
				$productArray[]		=	$product->getAttributeText('color');
				$productArray[]		=	$product->getAttributeText('size');
				$productArray[] 	= 	$product->getAttributeText('ages');
				$productArray[]		= 	$product->getAttributeText('departments');
				$productArray[]		=	$product->getAttributeText('final_sale');
				$productArray[]		=	$product->getFulfillmentType();
				$productArray[]		=	$product->getAttributeText('shipping_method');
				$productArray[]		=	number_format(round($product->getPrice(), 2), 2);
				$productArray[]		=	number_format(round($product->getSpecialPrice(), 2), 2);
				$productArray[]		=	number_format(round($product->getOriginalWholesale(), 2), 2);
				$productArray[]		=	number_format(round($product->getSaleWholesale(), 2), 2);
				$productArray[]		=	$product->getImage();
				$productArray[]		=	$product->getSmallImage();
				$productArray[]		=	$product->getThumbnail();
				$productArray[]		=	$product->getDescription();
				$productArray[]		=	$product->getAttributeText('is_master_pack') ? $product->getAttributeText('is_master_pack') : '';
				$productArray[]		=	$product->getCasePackQty();
				$productArray[]		=	$product->getShippingRate();
				$productArray[]		=	$product->getShippingLength();
				$productArray[]		=	$product->getShippingWidth();
				$productArray[]		=	$product->getShippingHeight();
				$productArray[]		=	$product->getGiftWrappingPrice();
				$productArray[]		=	$product->getShortDescription();
				$productArray[]		=	$product->getProductClass();
				$productArray[]		=	$product->getProductSubclass();
				$productArray[]		= 	$product->getAttributeText('hot_list') ? $product->getAttributeText('hot_list') : '';
				$productArray[]		=   $product->getAttributeText('featured') ? $product->getAttributeText('featured') : '';
				$productArray[]		=	number_format(round($product->getWeight(), 2), 2);	
				$productArray[]		=	number_format(round($product->getMsrp(), 2), 2);
				
				$csv.= implode(',', $productArray)."\n";
				$productArray = null;
			}
		}
		return $csv;
    }

    public function getIndProductSold($_category, $product) {
            $total_units = 0;

            $fromTime = strtotime($_category->getData('event_start_date')) - 60*60*24*7;    // 7 days before
            $toTime = strtotime($_category->getData('event_end_date')) + 60*60*24*3;    // 3 days after

            $fromDate = date('Y-m-d H:i:s', $fromTime);
            $toDate = date('Y-m-d H:i:s', $toTime);
            #retrieve all orders related to the item with in the specified time frame
            $ordersColl = Mage::getModel('sales/order_item')->getCollection();
            $ordersColl->getSelect()->where('product_id =' . $product->getEntityId() . ' and created_at between "' . $fromDate . '" and "' . $toDate . '"');
            
            foreach($ordersColl as $order) {
                #NOTE : parent item id refers to items with configurations
                if($order->getParentItemId()) {
                    $parent_item_id = $order->getParentItemId();
                    $parent_order_line = Mage::getModel('sales/order_item')->getCollection();
                    $parent_order_line->getSelect()->where('item_id =' . $parent_item_id);
                    $order = $parent_order_line->getFirstItem();
                }

                $qty = $order->getQtyOrdered() - $order->getQtyReturned() - $order->getQtyCanceled();
                $total_units += $qty;
           }

        return $total_units;
    }

    public function casePackOrderAmount($ratio, $cp_qty) {
        
        if($ratio){
            $order_amount = $ratio * $cp_qty;
        } else {
            $order_amount = $cp_qty;
        }

        return $order_amount;
    }

}