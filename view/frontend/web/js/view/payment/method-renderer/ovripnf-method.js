define(
  [
    'Magento_Checkout/js/view/payment/default'
  ],
  function (Component) {
    'use strict';
    return Component.extend({
      defaults: {
        template: 'Ovribanking_Ovri/payment/ovripnf'
      },
      getMailingAddress: function () {
        return window.checkoutConfig.payment.checkmo.mailingAddress;
      },
    });
  }
);