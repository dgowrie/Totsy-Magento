<?php
$installer = $this;
$installer->startSetup();
 
$installer->run("

DROP TABLE IF EXISTS {$this->getTable('totsy_catalog/product_purchase_limit')};
CREATE TABLE {$this->getTable('totsy_catalog/product_purchase_limit')} (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `purchase_count` int(10) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Product purchase limit';

");
 
$installer->endSetup();

Mage::log('done: '. $this->getTable('totsy_catalog/product_purchase_limit'));