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

class Harapartners_Stockhistory_Block_Adminhtml_Vendor_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'stockhistory';
        $this->_controller = 'adminhtml_vendor';
        
        
        if($this->getVendorId() && $this->_isAllowedAction('create_po')){
            $this->_addButton('create_po', array(
                'label'     => Mage::helper('stockhistory')->__('Create PO'),
                'onclick'   => 'setLocation(\'' . $this->getCreatePoUrl() .'\')',
                'class'        => 'add',
              ));
        }
        if(!$this->_isAllowedAction('delete')) {
            $this->_removeButton('delete');
        }

        if(!$this->_isAllowedAction('save')) {
            $this->_removeButton('reset');
            $this->_removeButton('save');
        }
        //$this->_updateButton('save', 'label', Mage::helper('stockhistory')->__('Import File'));
    }
    
    public function getHeaderText() {
        return Mage::helper('stockhistory')->__('Vendor Info');
    }

    public function getSaveUrl(){
        return $this->getUrl('*/*/save', array('_current'=>true));
    }

    protected function _isAllowedAction($action)
    {
        //return null;
        return Mage::getSingleton('admin/session')->isAllowed('harapartners/stockhistory/vendor/actions/' . $action);
    }
    
    public function getCreatePoUrl()
    {
        return $this->getUrl('stockhistory/adminhtml_purchaseorder/newByVendor', array('vendor_id' => $this->getVendorId()));
    }
    
    public function getVendorId()
    {   
        $vendorInfo = Mage::registry('stockhistory_vendor_data');
        return (!empty($vendorInfo['id']))?$vendorInfo['id']:null;
    }
}