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
      component: 'Ipsinternationnal_Ovri/js/view/payment/method-renderer/ovri-method'
    });
    return Component.extend({});
  }
);