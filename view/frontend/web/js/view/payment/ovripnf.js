define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    Component,
    rendererList
  ) {
    'use strict';
    rendererList.push({
      type: 'ovripnf',
      component: 'Ovribanking_Ovri/js/view/payment/method-renderer/ovripnf-method'
    });
    return Component.extend({});
  }
);