<?php

/**
 * @category    Totsy
 * @package     Totsy_Solrsearch
 * @author      Slavik Koshelevskyy <skosh@totsy.com>
 * @copyright   Copyright (c) 2012 Totsy LLC
 */

class Totsy_Solrsearch_Model_Resource_Engine extends Enterprise_Search_Model_Resource_Engine {
    public function __construct()
    {
        $this->_advancedDynamicIndexFields[] = '#category_name_level_';
        $this->_advancedDynamicIndexFields[] = '#date_';
        parent::__construct();
    }
}