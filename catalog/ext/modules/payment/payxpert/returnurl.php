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

  $merchantToken = $_SESSION['payxpertMerchantToken'];
  $data = $_POST["data"];

  // Setup the connection and redirect Status
  $c2pClient = new Connect2PayClient(MODULE_PAYMENT_PAYXPERT_URL, MODULE_PAYMENT_PAYXPERT_ORIGINATOR, MODULE_PAYMENT_PAYXPERT_PASSWORD);
  if ($c2pClient->handleRedirectStatus($data, $merchantToken)) {
  
    $status = $c2pClient->getStatus();
    // get the Error code
    $errorCode = $status->getErrorCode();
    // errorCode = 000 transaction is successfull
    if ($errorCode == '000') {
      // Display the success page
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'));
    } else {
      // Display the checkout page
      $message = "Transaction status: " . $status->getStatus() . " (Error code: " . $errorCode . ")";
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($message), 'SSL'));
    }
  } else {
      // Display the checkout page
      $message = "Incorrect Status";
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($message), 'SSL'));
  }
?>
