<?php

namespace Ipsinternationnal\Ovri\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session;

/**
 * Class TestObserver
 */
class InitPayment implements ObserverInterface
{

  protected $_storeManager;
  protected $_order;
  protected $_checkoutSession;

  public function __construct(
    \Magento\Sales\Api\Data\OrderInterface $order,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Checkout\Model\Session $checkoutSession
  ) {
    $this->_order = $order;
    $this->_storeManager = $storeManager;
    $this->_checkoutSession = $checkoutSession;
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    // Get order id
    $orderId = $observer->getEvent()->getOrderIds();

    // Get BaseUrl
    $base_url = $this->_storeManager->getStore()->getBaseUrl();
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

    // Get order
    $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId[0]);




    // Get Payment method
    $payment = $order->getPayment();
    $method = $payment->getMethodInstance();
    $redirect = $objectManager->get('\Magento\Framework\App\Response\Http');
    if ($method->getCode() == 'ovripnf' && $order->getStatus() == "pending") {
      $redirect->setRedirect($base_url . 'ipsinternationnal_ovri/index/redirect?id=' . $orderId[0] . '&Lease=3');
    } else if ($method->getCode() == 'ovri' && $order->getStatus() == "pending") {
      $redirect->setRedirect($base_url . 'ipsinternationnal_ovri/index/redirect?id=' . $orderId[0] . '&Lease=0');
    } else {
      return;
    }
  }
}
