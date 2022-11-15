define(
  [
    'Magento_Checkout/js/view/payment/default'
  ],
  function (Component) {
    'use strict';
    return Component.extend({
      defaults: {
        template: 'Ipsinternationnal_Ovri/payment/ovri'
      },
      getMailingAddress: function () {
        return window.checkoutConfig.payment.checkmo.mailingAddress;
      },
    });
  }
);