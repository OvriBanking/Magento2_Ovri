<?php

namespace Ipsinternationnal\Ovri\Observer;


class Email implements \Magento\Framework\Event\ObserverInterface
{

  // Don't send email with  gateway before payment is finished 
  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    try {
      $order = $observer->getEvent()->getOrder();
      $this->_current_order = $order;
      $payment = $order->getPayment();
      $method = $payment->getMethodInstance();
      if ($method->getCode() == 'ovri' || $method->getCode() == 'ovripnf') {
        $this->stopNewOrderEmail($order);
      }
    } catch (\ErrorException $ee) {
    } catch (\Exception $ex) {
    } catch (\Error $error) {
    }
  }

  // Stop sending Email
  public function stopNewOrderEmail(\Magento\Sales\Model\Order $order)
  {
    $order->setCanSendNewEmailFlag(false);
    $order->setSendEmail(false);
    try {
      $order->save();
    } catch (\ErrorException $ee) {
    } catch (\Exception $ex) {
    } catch (\Error $error) {
    }
  }
}
