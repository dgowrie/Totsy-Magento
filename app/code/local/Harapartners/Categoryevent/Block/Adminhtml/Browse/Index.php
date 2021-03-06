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
 
class Harapartners_Categoryevent_Block_Adminhtml_Browse_Index extends Mage_Adminhtml_Block_Widget_Container {
    
    public function __construct(){
        parent::__construct();
        $this->setTemplate('categoryevent/browse/index.phtml');
    }
    
    protected function _prepareLayout(){
        $this->setChild('grid', $this->getLayout()->createBlock('categoryevent/adminhtml_browse_index_grid', 'categoryevent.browse.index.grid'));
        return parent::_prepareLayout();
    }

    public function getGridHtml(){
        return $this->getChildHtml('grid');
    }

    public function isSingleStoreMode() {
        if (!Mage::app()->isSingleStoreMode()) {
               return false;
        }
        return true;
    }
    
 
}