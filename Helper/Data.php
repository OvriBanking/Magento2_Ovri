<?php

namespace Ovribanking\Ovri\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\ResultFactory;

class Data extends AbstractHelper
{
	protected $storeManager;
	protected $objectManager;
	protected $curlRequest;
	protected $resultFactory;
	protected $_messageManager;

	const XML_PATH = 'payment/ovri/';
	const XML_PATH_PNF = 'payment/ovripnf/';
	const MONETIGO_URI_INITIATE = 'https://api.ovri.app/payment/init_transactions/';
	const MONETIGO_URI_GET_TRANSACTION = 'https://api.ovri.app/payment/transactions/';
	const MONETIGO_URI_GET_TRANSACTION_BYOID = 'https://api.ovri.app/payment/transactions_by_merchantid/';

	public function __construct(
		Context $context,
		ObjectManagerInterface $objectManager,
		StoreManagerInterface $storeManager,
		Curl $curlRequest,
		ResultFactory $resultFactory,
		\Magento\Framework\Message\ManagerInterface $messageManager
	) {
		$this->objectManager = $objectManager;
		$this->storeManager  = $storeManager;
		$this->curlRequest = $curlRequest;
		$this->resultFactory = $resultFactory;
		$this->_messageManager = $messageManager;
		parent::__construct($context);
	}

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field,
			ScopeInterface::SCOPE_STORE,
			$storeId
		);
	}

	public function getGeneralConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH . $code, $storeId);
	}

	/* Redirection function
	@uri is a redirection address value of either path or direct url
	*/
	public function redirectHttp($uri)
	{
		$redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
		$redirect->setUrl($uri);
		return $redirect;
	}

	/* Private function of signature of request */
	private function signRequest($params, $beforesign = "")
	{
		$ShaKey = $this->getGeneralConfig('secretkey');
		foreach ($params as $key => $value) {
			$beforesign .= $value . "!";
		}
		$beforesign .= $ShaKey;
		$sign = hash("sha512", base64_encode($beforesign . "|" . $ShaKey));
		$params['SHA'] = $sign;
		return $params;
	}

	/* Ovri payment initiation request function
	@arg is Ovri Parameters
	used @Post curl request
	Json DECODE
	*/
	public function postOvri($args)

	{
		$SignedRequest = $this->signRequest($args);
		$this->curlRequest->post(self::MONETIGO_URI_INITIATE, $SignedRequest);
		$result = json_decode($this->curlRequest->getBody(), true);
		return $result;
	}

	/* Securise Api Request */

	/* Validate Transaction ID */
	// $result = json_decode( $this->getTransactions( $Request )[ 'body' ], true );

	/* Get payment status from MoneTigo */
	public function getOvriTransaction($transid)
	{
		/* Signature */
		$Request = $this->signRequest(array(
			'ApiKey' => $this->getGeneralConfig('merchantkey'),
			'TransID' => $transid
		));

		$this->curlRequest->get(self::MONETIGO_URI_GET_TRANSACTION . "?" . http_build_query($Request));
		$result = json_decode($this->curlRequest->getBody(), true);
		return $result;
	}

	/* Get payment status from MoneTigo */
	public function getOvriTransactionByOrderId($orderid)
	{
		/* Signature */
		$Request = $this->signRequest(array(
			'ApiKey' => $this->getGeneralConfig('merchantkey'),
			'MerchantOrderId' => $orderid
		));

		$this->curlRequest->get(self::MONETIGO_URI_GET_TRANSACTION_BYOID . "?" . http_build_query($Request));
		$result = json_decode($this->curlRequest->getBody(), true);
		return $result;
	}

	public function getPNFConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_PNF . $code, $storeId);
	}
}
