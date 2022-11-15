<?php


namespace Ovribanking\Ovri\Model\Config\Backend;

class VerifPNF extends \Magento\Framework\App\Config\Value
{

    public function beforeSave()
    {
        if (!is_numeric($this->getValue())) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Minimum amount of payment in 3 times is not a number.'));
        } else if ($this->getValue() < 50) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Minimum amount of payment in 3 times is smaller than 50€'));
        }
        $this->setValue(intval($this->getValue()));
        parent::beforeSave();
    }
}
