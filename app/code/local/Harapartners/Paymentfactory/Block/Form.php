<?php

class Harapartners_Paymentfactory_Block_Form extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentfactory/form.phtml');
    }

    protected function _getConfig()
    {
        return Mage::getSingleton('paymentfactory/config');
    }

   /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getProfilesByCustomerId( $customerId ) {
           $profiles = Mage::getModel('paymentfactory/profile')->getCollection();
           $profiles->getSelect()->where( 'customer_id = ?', $customerId );
           return $profiles;
    }
    
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }


    /*
    * solo/switch card start year
    * @return array
    */
     public function getSsStartYears()
    {
        $years = array();
        $first = date("Y");

        for ($index=5; $index>=0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = array(0=>$this->__('Year'))+$years;
        return $years;
    }

    /*
    * Whether switch/solo card type available
    */
    public function hasSsCardType()
    {
        $availableTypes = explode(',', $this->getMethod()->getConfigData('cctypes'));
        $ssPresenations = array_intersect(array('SS', 'SM', 'SO'), $availableTypes);
        if ($availableTypes && count($ssPresenations) > 0) {
            return true;
        }
        return false;
    }

    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }
    
    public function getFullCcCardType( $shortCardType ) {
        switch ( $shortCardType ) {
             case 'AE':
                 return 'American Express';
             case 'VI':
                 return 'Visa';
             case 'MC':
                 return 'MasterCard';
             case 'DI':
                 return 'Discover';
             default:
                 return $shortCardType;
         }
    }
}
