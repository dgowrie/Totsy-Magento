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
class Harapartners_Fulfillmentfactory_Block_Adminhtml_Itemqueue_Index_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct(){
        parent::__construct();
        $this->setId('itemqueueGrid');
        $this->setDefaultSort('created_at');    //sort by created_at desc
        $this->setDefaultDir('DESC');
    }

    protected function _prepareCollection(){
        $model = Mage::getModel('fulfillmentfactory/itemqueue');
        $collection = $model->getCollection();
        $collection->getSelect()
            ->joinLeft(array('cat_prod' =>'catalog_category_product'), 'main_table.product_id=cat_prod.product_id', array('cat_prod.category_id'))
            ->joinLeft(array('po' =>'stockhistory_purchaseorder'), 'cat_prod.category_id=po.category_id', array('po_name' => 'po.name', 'po_id' => 'po.id'))
            ->group(array('itemqueue_id')); 
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _getStore(){
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareColumns()
    {    
        $this->addColumn('order_increment_id', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Order No.'),
            'type'          => 'action',
            'align'         => 'center',
            'width'         => '50px',
            'index'         => 'order_increment_id',
            'getter'        => 'getOrderId',
            'actions'       => array(
                array(
                    'caption_data_key' => 'order_increment_id',
                    'url'              => array('base' => 'adminhtml/sales_order/view'),
                    'field'            => 'order_id'
                )
            )
        ));

        $this->addColumn('product_id', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Product ID'),
            'type'          => 'action',
            'align'         => 'center',
            'width'         => '100px',
            'index'         => 'product_id',
            'getter'        => 'getProductId',
            'filter_index'  => 'main_table.product_id',
            'actions'       => array(
                array(
                    'caption_data_key' => 'product_id',
                    'url'              => array('base' => 'adminhtml/catalog_product/edit'),
                    'field'            => 'id'
                )
            )
        ));

        $this->addColumn('name', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Name'),
            'align'         => 'center',
            'width'         => '150px',
            'index'         => 'name',
            'filter_index'  =>  'main_table.name'
        ));

        $this->addColumn('sku', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('SKU'),
            'align'         => 'center',
            'width'         => '100px',
            'index'         => 'sku'
        ));

        $this->addColumn('po_name', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Related PO'),
            'align'         => 'center',
            'width'         => '50px',
            'index'         => 'po_name',
            'filter_index'  => 'po.name',
            'renderer'       => 'Harapartners_Fulfillmentfactory_Block_Adminhtml_Itemqueue_Index_Renderer_POName'

        ));

        $this->addColumn('qty_ordered', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Qty Ordered'),
            'align'         => 'right',
            'width'         => '30px',
            'index'         => 'qty_ordered'
        ));

        $this->addColumn('fulfill_count', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Qty Fulfilled'),
            'align'         => 'right',
            'width'         => '30px',
            'index'         => 'fulfill_count'
        ));

        $this->addColumn('status', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Status'),
            'align'         => 'right',
            'width'         => '100px',
            'index'         => 'status',
            'filter_index'  =>  'main_table.status',
            'type'            => 'options',
            'options'        => Mage::helper('fulfillmentfactory')->getItemqueueStatusGridOptionList(),
            'renderer'        => 'Harapartners_Fulfillmentfactory_Block_Adminhtml_Itemqueue_Index_Renderer_Status',
        ));

        $this->addColumn('created_at', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Created At'),
            'align'         => 'center',
            'width'         => '150px',
            'index'         => 'created_at',
            'filter_index'  => 'main_table.created_at',
            'type'          => 'datetime',
            'gmtoffset'     => true
        ));

        $this->addColumn('updated_at', array(
            'header'        => Mage::helper('fulfillmentfactory')->__('Updated At'),
            'align'         => 'center',
            'width'         => '150px',
            'index'         => 'updated_at',
            'filter_index'  => 'main_table.created_at',
            'type'          => 'datetime',
            'gmtoffset'     => true
        ));

        return parent::_prepareColumns();
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('itemqueue_id');
        $this->getMassactionBlock()->setFormFieldName('itemqueue_id');
        $this->getMassactionBlock()->setUseSelectAll(false);
        if($this->_isAllowedAction('batch_cancel')){
            //batch cancel function
            $this->getMassactionBlock()->addItem('batch_cancel', array(
                 'label'=> Mage::helper('fulfillmentfactory')->__('Batch Cancel'),
                 'url'  => $this->getUrl('*/*/batchCancel'),
                 'confirm' => Mage::helper('fulfillmentfactory')->__('Are you sure?')
            ));
        }

        return $this;
    }

    protected function _addColumnFilterToCollection($column)
    {
        if($this->getCollection()) 
        { 
            if($column->getId() == 'po_name') {
                $cond = $column->getFilter()->getCondition();
                $this->getCollection()
                    ->addFieldToFilter($column->getFilterIndex() , $cond)
                    ->getSelect()
                    ->group(array('itemqueue_id'));
            }
        }
        
        return parent::_addColumnFilterToCollection($column);
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array(
                'store'=>$this->getRequest()->getParam('store'),
                'id'=>$row->getId()
        ));
    }

    protected function _isAllowedAction($action)
    {
        //return null;
        return Mage::getSingleton('admin/session')->isAllowed('sales/fulfillmentfactory/fulfillmentgrid/actions/' . $action);
    }
}
