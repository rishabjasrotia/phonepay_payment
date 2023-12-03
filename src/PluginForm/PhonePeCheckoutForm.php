<?php

namespace Drupal\phonepay_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Crypt;
use Drupal\commercepaytm\PaytmLibrary;

class PhonePeCheckoutForm extends BasePaymentOffsiteForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $paytm_library = new PaytmLibrary();
    //$paytm_helper = new PaytmHelper();
    $payment = $this->entity;

    $redirect_method = 'post';
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
    $order = Order::load($order_id);
    $user_id = \Drupal::currentUser()->id();
    $address = $order->getBillingProfile()->address->first();
    // $mode = $payment_gateway_plugin->getConfiguration()['pmode'];
    $mode = $payment_gateway_plugin->getConfiguration()['mode'];
    if($mode=='test'){
    $paytmDmain = 'https://securegw-stage.paytm.in/';
    $paytmInitTxnUrl = $paytmDmain.'theia/api/v1/initiateTransaction?mid=';
    }else{
    $paytmDmain = 'https://securegw.paytm.in/';
    $paytmInitTxnUrl = $paytmDmain.'theia/api/v1/initiateTransaction?mid=';
    }
    //$PAYTM_ENV
    //$transactionURL=$payment_gateway_plugin->getConfiguration()['merchant_transaction_url'];
    $customCallBackURL=$payment_gateway_plugin->getConfiguration()['merchant_transaction_custom_callback_url'];
    $merchant_id = $payment_gateway_plugin->getConfiguration()['merchant_id'];
    $merchant_key = $payment_gateway_plugin->getConfiguration()['merchant_key'];
    $merchant_website = $payment_gateway_plugin->getConfiguration()['merchant_website'];
    $merchant_industry_type = $payment_gateway_plugin->getConfiguration()['merchant_industry_type'];
    $merchant_channel_id = $payment_gateway_plugin->getConfiguration()['merchant_channel_id'];
    //$redirect_url = $transactionURL;
    $callback_url =  Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], array('absolute' => TRUE))->toString();
    if(trim($customCallBackURL)!=''){
      if(filter_var($customCallBackURL, FILTER_VALIDATE_URL)){
        $callback_url=$customCallBackURL;
      }
    }
    $paramList["MID"] = $merchant_id;
    $paramList["ORDER_ID"] = $order_id;
    $paramList["CUST_ID"] = $user_id;
    $paramList["INDUSTRY_TYPE_ID"] = 'Retail';
    $paramList["CHANNEL_ID"] = 'WEB';
    $paramList["TXN_AMOUNT"] = round($payment->getAmount()->getNumber(), 2);
    $paramList["CALLBACK_URL"] = $callback_url;
    $paramList["WEBSITE"] = $merchant_website;
    $paramList["INDUSTRY_TYPE_ID"] = $merchant_industry_type;
    $paramList["CHANNEL_ID"] = $merchant_channel_id;
    //$paramList['CHECKSUMHASH'] = $paytm_library->getChecksumFromArray($paramList,$merchant_key);
    $paytmParams = array();
            $paytmParams["body"] = $paramList;
            $paytmParams["body"] = array(
                                      "requestType" => "Payment",
                                      "mid" => $merchant_id,
                                      "websiteName" => $merchant_website,
                                      "orderId" =>$order_id,
                                      "callbackUrl" => $callback_url,
                                      "txnAmount" => array(
                                      "value" => round($payment->getAmount()->getNumber(), 2),
                                      "currency" => "INR",
                                    ),
                                    "userInfo" => array(
                                    "custId" => $user_id,
                                    ),
            );


     $generateSignature = $paytm_library->generateSignature(json_encode($paytmParams['body'], JSON_UNESCAPED_SLASHES), $merchant_key);

     $paytmParams["head"] = array(
      "signature" => $generateSignature
     );
    $post_data_string = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
    $apiURL = $paytmInitTxnUrl.$merchant_id.'&orderId='.$order_id;
    $ch = curl_init($apiURL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($post_data_string))
    );
    $jsonResponse = curl_exec($ch);


  $responseArray = json_decode($jsonResponse, true);
      $pluginVersion = PaytmConstants::PLUGIN_VERSION;
  if(!empty($responseArray['body']['txnToken'])){
    $txnToken = $responseArray['body']['txnToken'];
  }else{
    $txnToken = '';
  }
  if(!empty($txnToken)){
      echo '<div id="paytm-pg-spinner" class="paytm-pg-loader"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div><div class="bounce4"></div><div class="bounce5"></div><script type="application/javascript" crossorigin="anonymous" src="'.$paytmDmain.'merchantpgpui/checkoutjs/merchants/'.$merchant_id.'.js"></script>
        <script>
        function openJsCheckout() {
          var config = {
                      "root": "",
                      "flow": "DEFAULT",
                      "data": {
                          "orderId": "'.$order_id.'",
                          "token": "'.$txnToken.'",
                          "tokenType": "TXN_TOKEN",
                          "amount": "'.round($payment->getAmount()->getNumber(), 2).'"
                      },
          "integration": {
                          "platform": "Paytm_Drupal_Commerce",
                          "version": "8|'.$pluginVersion.'"
                      },
                      "merchant": {
                          "redirect": true
                      },
                      "handler": {

                          "notifyMerchant": function (eventName, data) {

                            if(eventName == "SESSION_EXPIRED" || eventName == "APP_CLOSED"){
                              location.reload();
                            }
                          }
                      }
                  };
                  if (window.Paytm && window.Paytm.CheckoutJS) {
                      // initialze configuration using init method
                      window.Paytm.CheckoutJS.init(config).then(function onSuccess() {
                          // after successfully updating configuration, invoke checkoutjs
                          window.Paytm.CheckoutJS.invoke();

                      }).catch(function onError(error) {
                        //  console.log("error => ", error);
                      });
                  }
                  }
                  setTimeout(function(){ openJsCheckout(); }, 3000);
                  </script>
      ';


    }else{

      echo '<script>alert("Something went wrong.Please try again.");</script>';
      echo '<script>window.location="'.base_path().'"</script>';
    }

  }

}