<?php

namespace Ovribanking\Ovri\Block;



class Display extends \Magento\Framework\View\Element\Template
{

  protected $_coreRegistry;

  public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Magento\Framework\Registry $coreRegistry)
  {
    $this->_coreRegistry = $coreRegistry;
    parent::__construct($context);
  }

  public function construct_phtml()
  {

    // Recover Data from Controller Redirect.php
    $postCollection = $this->_coreRegistry->registry('data_ovri');

    $postCollection = json_decode($postCollection);

    $form = "";

    // Create input form to redirect
    foreach ($postCollection as $key => $value) {
      $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
    }

    return __($form);
  }
}
