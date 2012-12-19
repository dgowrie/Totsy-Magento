<?php

/**
 * @category    Totsy
 * @package     Totsy_Solrsearch
 * @author      Slavik Koshelevskyy <skosh@totsy.com>
 * @copyright   Copyright (c) 2012 Totsy LLC
 */

class Totsy_Solrsearch_Model_Resource_Index extends Enterprise_Search_Model_Resource_Index
{
     /**
     * Return array of category, position and visibility data by products
     *
     * @param   int $storeId
     * @param   array $productIds
     * @param   bool $visibility      add visibility data to result
     * @return  array
     */
    protected function _getCatalogCategoryData($storeId, $productIds, $visibility = true)
    {
        $adapter = $this->_getWriteAdapter();
        $prefix  = $this->_engine->getFieldsPrefix();
        $columns = array(
            'product_id' => 'product_id',
            'parents'    => new Zend_Db_Expr("GROUP_CONCAT(IF(idx.is_parent = 1, idx.category_id, '') SEPARATOR ' ')"),
            'anchors'    => new Zend_Db_Expr("GROUP_CONCAT(IF(idx.is_parent = 0, idx.category_id, '') SEPARATOR ' ')"),
            'positions'  => new Zend_Db_Expr("GROUP_CONCAT(CONCAT(idx.category_id, '_', idx.position) SEPARATOR ' ')"),
        );
        if ($visibility) {
            $columns[] = 'visibility';
        }
        // FIXME - this will work for flat category catalog only !!!!
        $select = $adapter->select()
            ->from(array('idx'=>$this->getTable('catalog/category_product_index')), $columns)
            ->joinLeft(
                array('flat'=> 'catalog_category_flat_store_1'),     
                'flat.entity_id=idx.category_id', 
                array('level_names'=> 
                	new Zend_Db_Expr("GROUP_CONCAT(CONCAT_WS('#', idx.category_id, flat.level, flat.name) SEPARATOR '|')") 
                )
            )
            ->where('idx.product_id IN (?)', $productIds)
            ->where('idx.store_id = ?', $storeId)
            ->group('idx.product_id');
             
        $sql = $select->__toString();
        $result = array();
        foreach ($adapter->fetchAll($select) as $row) {
          $this->_processCalogCategory($result, $prefix, $row); 
        }

        Mage::log(
            array(
                'line'      =>__LINE__, 
                'method'    =>__METHOD__,
                //'sql'       => $sql,
                '$prefix'   => $prefix, 
                'result'    =>$result,
                //'r'         =>$r
            ),
            null,
            'category.index.log'
        );
        return $result;
    }   


     /**
     * Process category data ( like products )
     *
     * @param   array $result
     * @param   string $prefix
     * @param   array $row      
     * @return  void
     */
    protected function _processCalogCategory(&$result, &$prefix, &$row){
        $data = array(
            $prefix . 'categories'          => array_filter(explode(' ', $row['parents'])),
        );
        
        $this->_processDates($data,$prefix);
        
        unset($data[$prefix.'categories']);

        $result[$row['product_id']] = $data;
    }

    protected function _processDates(&$data,&$prefix){
        $starts = $ends = array();
        foreach($data[$prefix . 'categories'] as $categoryId){
            if (empty($categoryId)) { continue; }
            $category = Mage::getSingleton('catalog/category')->load($categoryId)->toArray();
            $start[] = $category['event_start_date'];
            $end[] =$category['event_end_date'];
            unset($category);
        }
        
        $this->_doDate($start);
        $this->_doDate($end,'r');

        $data[ $prefix.'date_start' ] = date('Y-m-d',$start).'T00:00:00Z';
        $data[ $prefix.'date_end' ] = date('Y-m-d',$end).'T23:59:59Z';
    }

    private function _doDate(&$date,$sort=null){
        $return = time();
        if (!empty($date) && is_array($date)){
            if (is_null($sort)){
                sort($date);
            } else if ($sort=='r'){
                rsort($date);
            }
            $return = strtotime($date[0]);
        }
        $date = $return; 
    }
}
