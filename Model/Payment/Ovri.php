<?php


namespace Ovribanking\Ovri\Model\Payment;

class Ovri extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "ovri";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}
