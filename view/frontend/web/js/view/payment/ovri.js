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
      type: 'ovri',
      component: 'Ovribanking_Ovri/js/view/payment/method-renderer/ovri-method'
    });
    return Component.extend({});
  }
);