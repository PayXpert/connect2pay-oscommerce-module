<?php
/**
 * PayXpert Payment Module.
 *
 * PHP dependencies:
 * PHP >= 5.2.0
 *
 * @version 1.0 (20131205)
 * @author Regis Vidal
 * @copyright 2013 Digital Media World
 *
 */ 
  chdir('../../../../');
  
  require ('includes/application_top.php');
  include ('includes/classes/Connect2PayClient.php');

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }
  
// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }
  $signature = md5(MODULE_PAYMENT_PAYXPERT_ORIGINATOR . '|' . $_POST['customerId'] . '|' . $_POST['cartId'] . '|' . $_POST['amount']. '|' . $_POST['currency']. '|' . $_POST['orderId']. '|' . MODULE_PAYMENT_PAYXPERT_PASSWORD);
  
  if ($signature == $_POST['signature']) {
    $c2pClient = new Connect2PayClient(MODULE_PAYMENT_PAYXPERT_URL, MODULE_PAYMENT_PAYXPERT_ORIGINATOR, MODULE_PAYMENT_PAYXPERT_PASSWORD);
    // Setup parameters
    $c2pClient->setOrderID($_POST['orderId']);
    $c2pClient->setCustomerIP($_SERVER["REMOTE_ADDR"]);
    $c2pClient->setPaymentType(Connect2PayClient::_PAYMENT_TYPE_CREDITCARD);
    $c2pClient->setPaymentMode(Connect2PayClient::_PAYMENT_MODE_SINGLE);
    $c2pClient->setShopperID($_POST['customerId']);
    $c2pClient->setCtrlCustomData($_POST['customerId']);
    $c2pClient->setShippingType(Connect2PayClient::_SHIPPING_TYPE_VIRTUAL);
    $c2pClient->setAmount($_POST['amount']);
    $c2pClient->setOrderDescription($_POST['description']);
    $c2pClient->setCurrency($_POST['currency']);
    $c2pClient->setShopperFirstName($_POST['firstname']);
    $c2pClient->setShopperLastName($_POST['lastname']);
    $c2pClient->setShopperAddress($_POST['street_address']);
    $c2pClient->setShopperZipcode($_POST['postcode']);
    $c2pClient->setShopperCity($_POST['city']);
    $c2pClient->setShopperState($_POST['state']);
    $c2pClient->setShopperCountryCode($_POST['country']);
    $c2pClient->setShopperPhone($_POST['phone']);
    $c2pClient->setShopperEmail($_POST['email']);
    $c2pClient->setCtrlRedirectURL(tep_href_link('ext/modules/payment/payxpert/returnurl.php', '', 'SSL'));
    $c2pClient->setCtrlCallbackURL(tep_href_link('ext/modules/payment/payxpert/callback.php', '', 'SSL'));

    // Validate our information
    if ($c2pClient->validate()) {
    
      // Setup the tranaction
      if ($c2pClient->prepareTransaction()) {
        // We save in session the customer info
        $_SESSION['payxpertMerchantToken'] = $c2pClient->getMerchantToken();
      
        // if setup is correct redirect to the payment page.
        header ('Location: ' . $c2pClient->getCustomerRedirectURL()); 
      } else {
        echo "error prepareTransaction: ";
        echo $c2pClient->getClientErrorMessage() . "\n";
      }
    } else {
      echo "error validate: ";
      echo $c2pClient->getClientErrorMessage() . "\n";
    }
  } else {
      echo "error incorrect signature\n";
  }
  
?>
