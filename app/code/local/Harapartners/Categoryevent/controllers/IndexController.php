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
 */

class Harapartners_Categoryevent_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction(){
        if (Mage::getModel('customer/session')->getAfterAuthUrl()){
            $url ='/'.Mage::getModel('customer/session')->getAfterAuthUrl().'.html';
             Mage::app()
                ->getResponse()
                ->setRedirect($url);
            Mage::getModel('customer/session')->unsetData('after_auth_url');
            return;
        } 
        $this->loadLayout();
        $this->renderLayout();        
    }
    
    public function topnavAction(){
        
        if(!!Mage::app()->getRequest()->getParam('departments')){
            $attributeType = 'departments';
        }elseif(!!Mage::app()->getRequest()->getParam('ages')){
            $attributeType = 'ages';
        }
        $attributeValue = Mage::app()->getRequest()->getParam($attributeType);
        
        if(!$attributeType || !$attributeValue){
            $this->_forward('index');
            return;
        }

        Mage::register('attrtype', $attributeType);
        Mage::register('attrvalue', $attributeValue);
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function ageAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function categoryAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    

}
