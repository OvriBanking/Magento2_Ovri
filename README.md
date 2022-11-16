## Ovri Payment Module for Magento 2.3.x to 2.4.x 

Tested up to version 2.4.1 should not cause technical problems on versions above 2.4.x
This payment module allows you to accept credit card payments through Ovri.com payment solution (https://www.ovri.com).


* Module version: 1.2.0
* Composer required : 2.0 or more

INSTALLATION AND ACTIVATION
===========================

### Installation with COMPOSER
#### This is the recommended installation because it allows quick updates, so prefer the installation with COMPOSER
> To install Ovri payment module on Magento you just need to enter the following command: 
> 
> ```console
> composer require ovribanking/module-ovri-magento2
> ```
> 
> depending on the case or your version of composing you may have to use a method with some ignorance
> 
> ```console
> composer require ovribanking/module-ovri-magento2 --ignore-platform-reqs
> ```
### Manual installation via FTP
> If you want to perform a manual installation it is also possible in this case you just have to create the following directories in the app/code directory of your Magento > installation: 
> ```
> Main directory : Ovribanking (please respect upper & lower case)
> A subdirectory : Ovri (please respect upper & lower case)
> 
> This will give app/code/Ovribanking/Ovri/
> ```
> Then copy the entire archive into the **Ovri** directory.
> 
> You will need to activate the module with the following commands: 
> ```console
> php bin/magento module:enable Ovribanking_Ovri --clear-static-content
> php bin/magento setup:upgrade
> php bin/magento setup:di:compile
> php bin/magento cache:flush
> ```

### Update installation with COMPOSER
> ##### Attention only works if you had installed the module the first time with COMPOSER.
> The update is simple, just enter the following command: 
> ```console
> composer update ovribanking/module-ovri-magento2 --ignore-platform-reqs
> ```

MODULE SETTINGS
===============
To connect the module to your Ovri merchant account you must:

## 1. Retrieving your API credentials
  * Add your website to your Ovri account
  * Retrieve the API key (Merchant Key)
  * Generated the SECRET key and retrieved it
## 2. Configure Magento plugins (Ovri)
  * Click on "Stores" > "Configuration"
  * Then on the "Sales" tab > "Payment methods".
  * Just fill in the sections corresponding to Ovri (MerchantKey & SecretKey)

NOTIFICATION DE PAIEMENT
========================
The OvriBanking payment module for Magento, does not require any configuration on the OVRI side because the notification information is natively integrated to this plugin.
If your website does not receive the answers, please check the IPN status in your transactions and if necessary click on "View" to understand the reason,
Make sure to allow if you have a firewall the ips of OVRI and/or the access to the ipn resource:

* [VotreSiteWeb]/ovribanking_ovri/index/ipn/


TEST MODE
==========

(**Note:** To test transactions, don't forget to switch your website (in your Ovri interface, in test/demo mode) and switch it to production when your tests are finished.

Once the test mode is active, the test cards will be displayed on the payment page.

### Don't forget to disable the test mode when you are ready to accept real transactions. 