<?php

declare(strict_types=1);

namespace Ipsinternationnal\Ovri\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface; //Csrf added
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException; //Csrf added
use Magento\Framework\App\RequestInterface; //Csrf added
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Sales\Model\Order;

class Ipn extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{


	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

	/**
	 * @var \Ipsinternationnal\Ovri\Model\IpnFactory
	 */
	protected $_ipnFactory;

	/**
	 * @var OrderFactory
	 */
	private $orderFactory;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Paypal\Model\IpnFactory $ipnFactory
	 * @param \Psr\Log\LoggerInterface $logger
	 * @param OrderFactory $orderFactory
	 */

	public function __construct(
		Context $context
	) {

		parent::__construct(
			$context
		);
	}

	/**
	 * @inheritDoc
	 */
	public function createCsrfValidationException(
		RequestInterface $request
	): ?InvalidRequestException {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function validateForCsrf(RequestInterface $request): ?bool
	{
		return true;
	}

	public function generateComment($results = NULL)
	{
		$commentIs = "";
		if ($results['Transaction_Status']) {
			$commentIs .= "<b>Status of the transaction</b><br>";
			foreach ($results['Transaction_Status'] as $key => $value) {
				$commentIs .= "" . $key . " : " . $value . " <br>";
			}
		}

		if ($results['Card']) {
			$commentIs .= "<b>Payment card info</b><br>";

			foreach ($results['Card'] as $key => $value) {
				$commentIs .= "" . $key . " : " . $value . " <br>";
			}
		}

		if ($results['Bank']) {
			$commentIs .= "<b>Ovri transaction reference</b><br>";
			foreach ($results['Bank'] as $key => $value) {
				$commentIs .= "" . $key . " : " . $value . " <br>";
			}
		}
		return $commentIs;
	}

	public function execute()
	{
		/* If no POST Value sent */
		if (!$this->getRequest()->isPost()) {
			echo "Only POST calls are allowed";
			http_response_code(403);
			return;
		}

		/* Get Post DATA */
		$post = filter_input_array(INPUT_POST);
		/* Load instance */
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		/* Load Ovri Helper */
		$helper = $objectManager->create('Ipsinternationnal\Ovri\Helper\Data');
		/* Si TransId is not definied*/
		if (!$post["TransId"]) {
			echo "The TransId value seems not to have been correctly transmitted";
			http_response_code(401);
			return;
		}
		/* Get transactions infos on Ovri */
		$checkTransactionStatus = $helper->getOvriTransaction($post["TransId"]);

		/* Get Order with transactions info from Ovri */

		$order = $objectManager->create('\Magento\Sales\Model\Order')->load($checkTransactionStatus['Merchant_Order_Id']);
		$order_data = $order->getData();

		/* Check order exist */
		if (!isset($order_data)) {
			http_response_code(400);
			echo "This transaction does not correspond to any order";
			return;
		}

		/* Check if order already processed  */

		if ($order->getStatus() == "processing") {
			http_response_code(400);
			echo "This order seems to have already been processed";
			return;
		}



		/* Check approved payment */
		if ($checkTransactionStatus['Transaction_Status']['State'] == 2) {
			/* PAYMENT IS APPROVED */
			/* Pass order to processing */
			$order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
			/* Sent mail order received */
			$order->setCanSendNewEmailFlag(true);
			/* insert true amount really paid */
			$order->setTotalPaid((float)$checkTransactionStatus["Financial"]["Total_Paid"]);
			/* set total due to 0 */
			$order->setTotalDue(0);

			/* set Ovri notification */
			$order->setOvriIpn(true);
			/* save order update */
			$order->save();


			/* Add order history */
			$order->addStatusHistoryComment($this->generateComment($checkTransactionStatus));
			/* save order update */
			$order->save();

			/* Email notification (Sent) */
			$emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
			$emailSender->send($order, true);

			echo "This transaction was successfully processed.";
		} else {
			if ($checkTransactionStatus['Transaction_Status']['State'] != 6) {
				/* PAYMENT IS DECLINED */
				/* Register canceled state */
				$order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
				$order->save();
				/* include comment */
				$order->addStatusHistoryComment($this->generateComment($checkTransactionStatus));
				$order->save();


				echo "This order was processed correctly. (Payment canceled and order in CANCEL status).";
			} else {
				echo "The payment is still being processed";
			}
		}
	}
}
