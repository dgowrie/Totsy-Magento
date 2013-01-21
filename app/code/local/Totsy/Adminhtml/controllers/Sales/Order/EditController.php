<?php
/**
 * Adminhtml sales orders creation process controller
 *
 * @category   Totsy
 * @package    Totsy_Adminhtml
 * @author      Tom Royer <troyer@totsy.com>
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/EditController.php';

class Totsy_Adminhtml_Sales_Order_EditController extends Mage_Adminhtml_Sales_Order_EditController
{
    public function saveAction()
    {
        try {
            $customerId = $this->_getOrderCreateModel()->getQuote()->getCustomerId();
            $profile = Mage::getModel('paymentfactory/profile');
            $this->_processActionData('save');
            if ($paymentData = $this->getRequest()->getPost('payment')) {
                
                if(array_key_exists('cc_number', $paymentData)) {
                    $profile->loadByCcNumberWithId($paymentData['cc_number'].$customerId.$paymentData[ 'cc_exp_year' ].$paymentData[ 'cc_exp_month' ]);
                }
                if(!!$profile && !!$profile->getId()){
                    $cybersourceIdEncrypted = $profile->getEncryptedSubscriptionId();
                    if($cybersourceIdEncrypted) {
                        $paymentData['cybersource_subid'] = $cybersourceIdEncrypted;
                    }
                }
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }

            $order = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($this->getRequest()->getPost('order'))
                ->createOrder();
            //Make Order Created date same as the original
            $originalIncrementId = $order->getOriginalIncrementId();
            if($originalIncrementId) {
                $originalOrder = Mage::getModel('sales/order')->loadByIncrementId($originalIncrementId);
                if($originalOrder && $originalOrder->getId()) {
                     $order->setData('created_at', $originalOrder->getData('created_at'))
                           ->save();
                }
            }
            $this->_getSession()->clear();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if( !empty($message) ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        } catch (Mage_Core_Exception $e){
            $message = $e->getMessage();
            if( !empty($message) ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        }
        catch (Exception $e){
            $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
            $this->_redirect('*/*/');
        }
    }

    public function submitFulfillmentAction()
    {
        $orderId = $this->getRequest()->getParam('id');
        $order   = Mage::getModel('sales/order')->load($orderId);

        // ensure the order's line items have been fulfilled
        if (!$order->isReadyForFulfillment()) {
            Mage::getSingleton('adminhtml/session')->addError('Order could not be submitted for fulfillment at this time, because at least one order item has not yet been fulfilled.');
            $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
        } else {
            $result = Mage::getSingleton('fulfillmentfactory/service_dotcom')
                ->submitOrdersToFulfill(array($order));

            if (count($result)) {
                Mage::getSingleton('adminhtml/session')->addError((string)$result[0]->order_error->error_description);
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess('Order successfully sent for fulfillment.');
            }
        }

        $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
    }
}