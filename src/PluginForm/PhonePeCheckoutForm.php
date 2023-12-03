<?php

namespace Drupal\phonepay_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Routing\TrustedRedirectResponse;


use Drupal\phonepay_payment\PhonePe;

class PhonePeCheckoutForm extends BasePaymentOffsiteForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    $payment = $this->entity;

    $redirect_method = 'post';
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
    $order = Order::load($order_id);
    $user_id = \Drupal::currentUser()->id();
    $address = $order->getBillingProfile()->address->first();
    $billing_profile = $order->getBillingProfile();
    $phone = $billing_profile->get('field_mobile')->value;
    // $mode = $payment_gateway_plugin->getConfiguration()['pmode'];
    $mode = $payment_gateway_plugin->getConfiguration()['mode'];
    if($mode == 'test') {
      $phonePeENV = 'DEV';
    } else {
      $phonePeENV = 'PROD';
    }

    //$transactionURL=$payment_gateway_plugin->getConfiguration()['merchant_transaction_url'];
    $phonepe_merchant_id = $payment_gateway_plugin->getConfiguration()['phonepe_merchant_id'];
    $phonepe_merchant_user_id = $payment_gateway_plugin->getConfiguration()['phonepe_merchant_user_id'];
    $phonepe_salt_key = $payment_gateway_plugin->getConfiguration()['phonepe_salt_key'];
    $phonepe_salt_index = $payment_gateway_plugin->getConfiguration()['phonepe_salt_index'];
    //$redirect_url = $transactionURL;

    $customCallBackURL = $payment_gateway_plugin->getConfiguration()['merchant_transaction_custom_callback_url'];
    $callback_url =  Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], array('absolute' => true))->toString();
    if(trim($customCallBackURL) != '') {
      if(filter_var($customCallBackURL, FILTER_VALIDATE_URL)) {
        $callback_url = $customCallBackURL;
      }
    }
    // Below are the Test Details for Standard Checkout UAT, you can get your own from PhonePe Team. Make sure to keep the Salt Key and Salt Index safe (in environment variables or .env file).
    $phonepe = PhonePe::init(
      $phonepe_merchant_id, // Merchant ID
      $phonepe_merchant_user_id, // Merchant User ID
      $phonepe_salt_key, // Salt Key
      $phonepe_salt_index, // Salt Index
      $redirect_url, // Redirect URL, can be defined on per transaction basis
      $callback_url, // Callback URL, can be defined on per transaction basis
      $phonePeENV // or "PROD"
    );

    $amountInPaisa = round($payment->getAmount()->getNumber(), 2) * 100; // Amount in Paisa
    $userMobile = $phone; // User Mobile Number
    $transactionID = $order_id; // Transaction ID to track and identify the transaction, make sure to save this in your database.
    $redirectURL = $phonepe->standardCheckout()->createTransaction($amountInPaisa, $userMobile, $transactionID)->getTransactionURL();

    \Drupal::logger('phonepay_payment')->notice("Redirect URL: " . $redirectURL . PHP_EOL);
    return $this->buildRedirectForm($form, $form_state, $redirectURL, [], self::REDIRECT_GET);
  }

}