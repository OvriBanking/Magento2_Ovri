<?php

namespace Ipsinternationnal\Ovri\Controller\Index;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Mage;

class CustomerReturn extends \Magento\Framework\App\Action\Action
{
	/* @var \Magento\Checkout\Model\Session */
	protected $checkoutSession;
	/* @var \Magento\Quote\Model\QuoteFactory */
	protected $quoteFactory;
	/* @var \Magento\Quote\Model\QuoteRepository */
	protected $quoteRepository;
	/* @var \Magento\Sales\Model\Order */
	protected $orderInterface;
	/* @var \Magento\Customer\Model\Session $customerSession */
	protected $customerSession;
	/**
	 * @var \Psr\Log\LoggerInterface $logger
	 */
	protected $logger;
	protected $_blockFactory;

	/** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory **/
	protected $resultFactory;

	protected $_messageManager;
	public function __construct(
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Quote\Model\QuoteFactory $quoteFactory,
		\Magento\Quote\Model\QuoteRepository $quoteRepository,
		\Magento\Sales\Model\Order $orderInterface,
		\Magento\Customer\Model\Session $customerSession,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Element\BlockFactory $blockFactory,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Message\ManagerInterface $messageManager
	) {
		$this->checkoutSession = $checkoutSession;
		$this->quoteFactory = $quoteFactory;
		$this->quoteRepository = $quoteRepository;
		$this->orderInterface = $orderInterface;
		$this->customerSession = $customerSession;
		$this->logger = $logger;
		$this->_blockFactory = $blockFactory;
		$this->resultPageFactory = $resultPageFactory;
		$this->_messageManager = $messageManager;
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
		// Treat response
		$order = $this->orderInterface;
		$gettingorder = filter_input_array(INPUT_GET);
		if (isset($gettingorder['mtgo_id'])) {
			$order->loadByIncrementId($gettingorder['mtgo_id']);
			switch ($order->getStatus()) {
				case 'processing':
					$curQuote = $this->getCheckoutSession()->getQuote();
					$curQuote->setIsActive(false);
					$this->quoteRepository->save($curQuote);
					if ($order->getId()) { //Order Found
						$checkoutSession  = $this->getCheckoutSession();
						$checkoutSession->setLastOrderId($order->getId());
						$checkoutSession->setLastQuoteId($order->getQuoteId());
						$checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
						$order->addCommentToStatusHistory(
							_('Customer returned successfully from Ovri payment platform.')
						)->save();
					}
					$curQuote = $this->getCheckoutSession()->getQuote();
					$curQuote->setIsActive(false);
					$this->quoteRepository->save($curQuote);
					// Set redirect URL
					$response['redirect_url'] = 'checkout/onepage/success';
					break;
				case 'pending':
					//Initialize only if needed 
					$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					// Get helper config (Data from admin)
					$OvriConfiguration = $objectManager->create('Ipsinternationnal\Ovri\Helper\Data');
					$results = $OvriConfiguration->getOvriTransactionByOrderId($order->getIncrementId());
					if ($results['Transaction_Status']['State'] == 2) {
						$order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
						//Sent mail Order received
						$order->setCanSendNewEmailFlag(true);
						//Include amount paid
						$order->setTotalPaid((float)$results["Financial"]["Total_Paid"]);
						//Set amount du to 0
						$order->setTotalDue(0);
						//Valid order
						$order->save();
						//Redirect to success page !
						$curQuote = $this->getCheckoutSession()->getQuote();
						$curQuote->setIsActive(false);
						$this->quoteRepository->save($curQuote);
						if ($order->getId()) { //Order Found
							$checkoutSession  = $this->getCheckoutSession();
							$checkoutSession->setLastOrderId($order->getId());
							$checkoutSession->setLastQuoteId($order->getQuoteId());
							$checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
							$order->addCommentToStatusHistory(
								_('Customer returned successfully from Ovri payment platform by Return Page.')
							)->save();
						}
						$curQuote = $this->getCheckoutSession()->getQuote();
						$curQuote->setIsActive(false);
						$this->quoteRepository->save($curQuote);
						// Set redirect URL
						$response['redirect_url'] = 'checkout/onepage/success';
					} else if ($results['Transaction_Status']['State'] == 6) {
						echo '<html><head><meta http-equiv="refresh" content="5"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body>';
						echo '<center><div style="border: 1px solid black; width: 40%; min-width:300px; display:block;"><h3>' . _('Transaction being processed.') . '</h3>
			<span style="font-size: 12px;">' . _('Please wait, this can take up to 5 minutes.') . '</span><br>
<span style="font-size: 12px; color:red; font-weight:bold;">' . _('DO NOT CLOSE THIS PAGE !') . '</span></div></center>';
						echo "</body></html>";
						exit();
					} else {
						$order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
						$order->save();
						$order->addCommentToStatusHistory(
							_('Customer returned successfully from Ovri payment platform after failed payment.')
						)->save();
						//Restore cart after cancelled order
						$FactoryQuote = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
						$quote = $FactoryQuote->create()->loadByIdWithoutStore($order->getQuoteId());
						if ($quote->getId()) {
							$quote->setIsActive(1)->setReservedOrderId(null)->save();
							$checkoutSession  = $this->getCheckoutSession();
							$checkoutSession->replaceQuote($quote);
						}
						$response['redirect_url'] = 'checkout/cart';
						$this->_messageManager->addError(_('Pay was declined or cancelled.'));
					}
					break;
				default:
					$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					//Payment rejected
					//Restore cart after cancelled order
					$FactoryQuote = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
					$quote = $FactoryQuote->create()->loadByIdWithoutStore($order->getQuoteId());
					if ($quote->getId()) {
						$quote->setIsActive(1)->setReservedOrderId(null)->save();
						$checkoutSession  = $this->getCheckoutSession();
						$checkoutSession->replaceQuote($quote);
					}
					$response['redirect_url'] = 'checkout/cart';
					$this->_messageManager->addError(_('Pay was declined or cancelled.'));
					break;
			}
		} else {
			$this->_messageManager->addErrorMessage(__("An internal error occurred!"));
			$response['redirect_url'] = 'checkout/cart';
		}
		$resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
		$resultRedirect->setPath($response['redirect_url']);
		return $resultRedirect;
	}
}
