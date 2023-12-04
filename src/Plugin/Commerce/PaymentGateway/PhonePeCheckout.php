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

    $form['phonepe_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant Id'),
      '#default_value' => $this->configuration['phonepe_merchant_id'],
      '#required' => TRUE,
    ];

    $form['phonepe_merchant_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant User Id'),
      '#default_value' => $this->configuration['phonepe_merchant_user_id'],
      '#required' => TRUE,
    ];

    $form['phonepe_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant API Key'),
      '#default_value' => $this->configuration['phonepe_api_key'],
      '#required' => FALSE,
    ];

    $form['phonepe_salt_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Salt Key'),
      '#default_value' => $this->configuration['phonepe_salt_key'],
      '#required' => TRUE,
    ];

    $form['phonepe_salt_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Salt Index'),
      '#default_value' => $this->configuration['phonepe_salt_index'],
      '#required' => TRUE,
    ];

    $form['phonepe_merchant_website'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant Website'),
      '#default_value' => $this->configuration['phonepe_merchant_website'],
      '#required' => FALSE,
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
      $this->configuration['phonepe_salt_key'] = $values['phonepe_salt_key'];
      $this->configuration['phonepe_salt_index'] = $values['phonepe_salt_index'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // Move to custom controller
  }
}
