<?php

namespace Ipsinternationnal\Ovri\Controller\Index;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Action\Action;
use \Magento\Framework\Registry;
use \Magento\Framework\Exception\StateException;
use \Magento\Framework\Message\ManagerInterface;

class Redirect extends Action
{
  protected $_messageManager;
  protected $_coreRegistry;
  protected $checkoutSession;
  public function __construct(
    \Magento\Checkout\Model\Session $checkoutSession,
    Context $context,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    Registry $resultRegistry
  ) {
    $this->checkoutSession = $checkoutSession;
    $this->_messageManager = $messageManager;
    $this->_coreRegistry = $resultRegistry;
    parent::__construct($context);
  }
  /**
   * Get checkout session
   *
   * @return  \Magento\Checkout\Model\Session
   */
  public function getCheckoutSession()
  {
    return $this->checkoutSession;
  }
  public function execute()
  {
    /* Get Ovri Parameter and Helper load */
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $helper = $objectManager->create('Ipsinternationnal\Ovri\Helper\Data');
    /* Get Order by ID */
    $order = $objectManager->create('\Magento\Sales\Model\Order')->load(intval($_GET['id']));
    $order_data = $order->getData();
    if (!empty($order_data['customer_id'])) {
      // Get Customer by id
      $customer = $objectManager->create('\Magento\Customer\Model\Customer')->load(intval($order_data['customer_id']));
      $customer_data = $customer->getData();
      // Get Address by id
      $address = $objectManager->create('\Magento\Customer\Model\Address')->load(intval($customer_data['default_shipping']));
      $address_data = $address->getData();
    }
    /* Get Store config */
    $store = $objectManager->get('\Magento\Framework\Locale\Resolver');
    $lang = explode("_", $store->getLocale());
    /* Get BASE Url */
    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    $baseUrl = $storeManager->getStore()->getBaseUrl();
    /*
    Construction of the payment request according to : 
    1) Order in guest mode or with customer account
    2) Standard payment or in installments
    */
    $requestToken = array(
      'MerchantKey' => $helper->getGeneralConfig('merchantkey'),
      'amount' => number_format($order_data['grand_total'], 2, '.', ''),
      'RefOrder' => $order->getIncrementId(),
      'urlIPN' => $baseUrl . "ipsinternationnal_ovri/index/ipn/",
      'extension' => 'Magento2-1.1.0'
    );
    /*
    If payment is made in installments, the value of the lease is passed on to you.
    */
    if (isset($_GET['Lease']) && $_GET['Lease'] == 3) {
      $requestToken['Lease']          = "3";
    }
    /* If the order is made with a customer account */
    if (!empty($order_data['customer_id'])) {
      $requestToken['urlOK']     = $baseUrl . "sales/order/view/order_id/" . $_GET['id'];
      $requestToken['urlKO']     = $baseUrl . "sales/order/view/order_id/" . $_GET['id'];
      $requestToken['Customer_Name']     = $order_data['customer_lastname'];
      $requestToken['Customer_FirstName']     = $order_data['customer_firstname'];
      $requestToken['Customer_Email']     = $order_data['customer_email'];
    } else {
      /*
      If the command is in guest mode
      */
      $objectManagerCInfo = \Magento\Framework\App\ObjectManager::getInstance();
      $CustomerInformations = $objectManagerCInfo->create('Magento\Sales\Model\Order')->load($_GET['id']); // pass orderId
      $requestToken['urlOK']     = $baseUrl . "ipsinternationnal_ovri/index/CustomerReturn?mtgo_id=" . $order->getIncrementId() . "&mtgo_lname=" . $order->getBillingAddress()->getLastname() . "";
      $requestToken['urlKO']     = $baseUrl . "ipsinternationnal_ovri/index/CustomerReturn?mtgo_id=" . $order->getIncrementId() . "&mtgo_lname=" . $order->getBillingAddress()->getLastname() . "";
      $requestToken['Customer_Name']     = $CustomerInformations->getBillingAddress()->getLastname();
      $requestToken['Customer_FirstName']     = $CustomerInformations->getBillingAddress()->getFirstname();
      $requestToken['Customer_Phone']     = $CustomerInformations->getBillingAddress()->getTelephone();
      $requestToken['Customer_Email']     = $order_data['customer_email'];
    }
    /* In-session recording of payment request information */
    $this->_coreRegistry->register('data_ovri', json_encode($requestToken));
    /* Call from Ovri to obtain the payment token */
    $initiatePayment = $helper->postOvri($requestToken);
    /* Redirection to the payment page */

    if (!isset($initiatePayment['DirectLinkIs'])) {

      /* restore cart and display error */
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      //Payment rejected
      //Restore cart after cancelled order
      $FactoryQuote = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
      $quote = $FactoryQuote->create()->loadByIdWithoutStore($_GET['id']);
      if ($quote->getId()) {
        $quote->setIsActive(1)->setReservedOrderId(null)->save();
        $checkoutSession  = $this->getCheckoutSession();
        $checkoutSession->replaceQuote($quote);
      }

      $this->_messageManager->addError(__('Ovri Error : ' . json_encode($initiatePayment)));
      return $helper->redirectHttp('../../checkout/cart');
    } else {
      return $helper->redirectHttp($initiatePayment['DirectLinkIs']);
    }
  }
}
