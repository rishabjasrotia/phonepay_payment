<?php

namespace Drupal\phonepay_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Crypt;

/**
 * Provides the PhonePe payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "phonepay_payment",
 *   label = @Translation("PhonePe Payment"),
 *   display_label = @Translation("PhonePe"),
 *    forms = {
 *     "offsite-payment" = "Drupal\phonepay_payment\PluginForm\PhonePeCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */

class PhonePeCheckout extends OffsitePaymentGatewayBase {
  /**
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // dpm($form);
    $merchantID='';
    if(isset($this->configuration['phonepe_merchant_id'])){
      $merchantID=$this->configuration['phonepe_merchant_id'];
    }
    $form['phonepe_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant Id'),
      '#default_value' => $merchantID,
      '#required' => TRUE,
    ];
    $merchantUserID='';
    if(isset($this->configuration['phonepe_merchant_user_id'])){
      $merchantUserID=$this->configuration['phonepe_merchant_user_id'];
    }
    $form['phonepe_merchant_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant User Id'),
      '#default_value' => $merchantUserID,
      '#required' => TRUE,
    ];
    $merchantApiKEY='';
    if(isset($this->configuration['phonepe_api_key'])){
      $merchantApiKEY=$this->configuration['phonepe_api_key'];
    }
    $form['phonepe_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant API Key'),
      '#default_value' => $merchantApiKEY,
      '#required' => TRUE,
    ];
    $merchantWEB='';
    if(isset($this->configuration['phonepe_merchant_website'])){
      $merchantWEB=$this->configuration['phonepe_merchant_website'];
    }
    $form['phonepe_merchant_website'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant Website'),
      '#default_value' => $merchantWEB,
      '#required' => TRUE,
    ];
    $phonepe_pay_url='';
    if(isset($this->configuration['phonepe_pay_url'])){
      $phonepe_pay_url=$this->configuration['phonepe_pay_url'];
    }
    $form['phonepe_pay_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe API URL'),
      '#default_value' => $phonepe_pay_url,
      '#required' => TRUE,
    ];
    $merchantIndustryType='';
    if(isset($this->configuration['merchant_industry_type'])){
        $merchantIndustryType=$this->configuration['merchant_industry_type'];
    }

    $merchantCUSTCALLBACKURL='';
    if(isset($this->configuration['phonepe_redirect_url'])){
        $merchantCUSTCALLBACKURL=$this->configuration['phonepe_redirect_url'];
    }
    $form['phonepe_redirect_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom Call Back URL (if you want)'),
        '#default_value' => $merchantCUSTCALLBACKURL,
        '#required' => false,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['phonepe_merchant_id'] = $values['phonepe_merchant_id'];
      $this->configuration['phonepe_merchant_user_id'] = $values['phonepe_merchant_user_id'];
      $this->configuration['phonepe_api_key'] = $values['phonepe_api_key'];
      $this->configuration['phonepe_merchant_website'] = $values['phonepe_merchant_website'];
      $this->configuration['phonepe_redirect_url'] = $values['phonepe_redirect_url'];
      $this->configuration['phonepe_pay_url'] = $values['phonepe_pay_url'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $paramlist = array();
    $txnid                     = $request->get('TXNID');
    $paramlist['RESPCODE']     = $request->get('RESPCODE');
    $paramlist['RESPMSG']      = $request->get('RESPMSG');
    $paramlist['STATUS']       = $request->get('STATUS');
    $paramlist['MID']          = $request->get('MID');
    $paramlist['TXNAMOUNT']    = $request->get('TXNAMOUNT');
    $paramlist['ORDERID']      = $txnid;
    $paramlist['CHECKSUMHASH'] = $request->get('CHECKSUMHASH');
    $valid_checksum=TRUE;

    if($valid_checksum) {
        $a = 0;
        if($paramlist['STATUS'] == 'TXN_SUCCESS') {
            $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
            $payment = $payment_storage->create([
                'state' => 'authorization',
                'amount' => $order->getTotalPrice(),
                'payment_gateway' => $this->entityId,
                'order_id' => $order->id(),
                'test' => $this->getMode() == 'test',
                'remote_id' => $order->id(),
                'remote_state' => $paramlist['STATUS'],
                'authorized' => $this->time->getRequestTime(),
            ]);
            $payment->save();
            drupal_set_message($this->t('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id', ['@orderid' => $order->id(), '@transaction_id' => $txnid]));
        }
        else {
            drupal_set_message($this->t('Transaction Failed'), 'error');
        }
    }
    else {
        drupal_set_message($this->t('Checksum mismatched.'), 'error');
    }
  }
}
