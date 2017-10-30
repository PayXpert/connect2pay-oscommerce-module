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
 
  // Send a response to mark this transaction as notified
  $response = array("status" => "OK", "message" => "Status recorded");
  header("Content-type: application/json");
  echo json_encode($response);
 
  chdir('../../../../');
  
  require ('includes/application_top.php');
  include ('includes/classes/Connect2PayClient.php');

  $c2pClient = new Connect2PayClient(MODULE_PAYMENT_PAYXPERT_URL, MODULE_PAYMENT_PAYXPERT_ORIGINATOR, MODULE_PAYMENT_PAYXPERT_PASSWORD);
  if ($c2pClient->handleCallbackStatus()) {
  
    // get the Error code
    $status = $c2pClient->getStatus();
    $errorCode = $status->getErrorCode();
    $errorMessage = $status->getErrorMessage();
    $merchantData = $status->getCtrlCustomData();
    $transactionId = $status->getTransactionID();
    $orderId = $status->getOrderID();
    $success = false;
    $message = "Unknow error";

    $order_query = tep_db_query("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . (int)$orderId . "' and customers_id = '" . (int)$merchantData . "'");
    if (tep_db_num_rows($order_query) > 0) {
      $order = tep_db_fetch_array($order_query);
      
      $message = "PayXpert payment module:\n";
      $message .= "Received a new transaction status callback from " . $_SERVER["REMOTE_ADDR"] . ".\n";
      $message .= "Error code: " . $errorCode . "\n";
      $message .= "Error message: " . $errorMessage . "\n";
      $message .= "Transaction ID: " . $transactionId . "\n";
      
      syslog (LOG_DEBUG, str_replace("\n", ", ", $message));
      
      if ($errorCode == '000') {
        if ($order['orders_status'] == MODULE_PAYMENT_PAYXPERT_PREPARE_ORDER_STATUS_ID) {
          $order_status_id = (MODULE_PAYMENT_PAYXPERT_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAYXPERT_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID);

          tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . $order_status_id . "', last_modified = now() where orders_id = '" . (int)$orderId . "'");
        }
      }
      $sql_data_array = array('orders_id' => $orderId,
                              'orders_status_id' => $order_status_id,
                              'date_added' => 'now()',
                              'customer_notified' => '0',
                              'comments' => $message);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    } else {
      syslog(LOG_ERR, "PayXpert payment module: unable find order ID: " . (int)$orderId . " with customer ID: " . (int)$merchantData . " from " . TABLE_ORDERS);
    }
  } else {
    syslog(LOG_ERR, "PayXpert payment module: Callback received an incorrect status from " . $_SERVER["REMOTE_ADDR"]);
  }
?>