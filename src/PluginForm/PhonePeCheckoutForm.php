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
    $redirect_method = 'post';
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $user_id = \Drupal::currentUser()->id();
    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();

    // Order Details
    $order = Order::load($order_id);
    $address = $order->getBillingProfile()->address->first();

    // Blling Profile
    $billing_profile = $order->getBillingProfile();
    $phone = $billing_profile->get('field_mobile')->value;
    
    $mode = $payment_gateway_plugin->getConfiguration()['mode'];
    if($mode == 'test') {
      $phonePeENV = 'DEV';
    } else {
      $phonePeENV = 'PROD';
    }

    // PhonePe Transaction details
    //$transactionURL=$payment_gateway_plugin->getConfiguration()['merchant_transaction_url'];
    $phonepe_merchant_id = $payment_gateway_plugin->getConfiguration()['phonepe_merchant_id'];
    $phonepe_merchant_user_id = $payment_gateway_plugin->getConfiguration()['phonepe_merchant_user_id'];
    $phonepe_salt_key = $payment_gateway_plugin->getConfiguration()['phonepe_salt_key'];
    $phonepe_salt_index = $payment_gateway_plugin->getConfiguration()['phonepe_salt_index'];
    //$redirect_url = $transactionURL;

    // $callback_url =  Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], array('absolute' => true))->toString();
    $redirect_url =  Url::FromRoute('phonepay_payment.redirect_url', ['order_id' => $order_id], array('absolute' => true))->toString();
    $callback_url  = Url::FromRoute('phonepay_payment.callback_url', ['order_id' => $order_id], array('absolute' => true))->toString();;

    // UAT
    if ($phonePeENV == 'DEV') {
      $phonepe_merchant_id = 'PGTESTPAYUAT';
      $phonePeENV = 'DEV';
      $phonepe_salt_key = '099eb0cd-02cf-4e2a-8aca-3e6c6aff0399';
      $phonepe_salt_index = 1;
    }
    // Create PhonePe transaction request
    $phonepe = PhonePe::init(
      $phonepe_merchant_id, // Merchant ID
      $phonepe_merchant_user_id, // Merchant User ID
      $phonepe_salt_key, // Salt Key
      $phonepe_salt_index, // Salt Index
      $redirect_url, // Redirect URL, can be defined on per transaction basis
      $callback_url, // Callback URL, can be defined on per transaction basis
      $phonePeENV // "DEV" or "PROD"
    );

    $amountInPaisa = round($payment->getAmount()->getNumber(), 2) * 100; // Amount in Paisa.
    $userMobile = $phone; // User Mobile Number.
    $transactionID = $order_id; // Transaction ID to track and identify the transaction.
    $redirectURL = $phonepe->standardCheckout()->createTransaction($amountInPaisa, $userMobile, $transactionID)->getTransactionURL();
   
    $order->setData('phonepe_request_identifier', $redirectURL);
    $order->save();

    \Drupal::logger('phonepay_payment')->notice("Redirect URL: " . $redirectURL . PHP_EOL);

    return $this->buildRedirectForm($form, $form_state, $redirectURL, [], self::REDIRECT_GET);
  }

}
