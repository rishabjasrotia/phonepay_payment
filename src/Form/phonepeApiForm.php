<?php

namespace Drupal\phonepay_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class phonepeApiForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'phonepay_payment.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phonepay_payment_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['phonepe_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant ID'),
      '#default_value' => $config->get('phonepe_merchant_id'),
    ];

    $form['phonepe_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Merchant User ID'),
      '#default_value' => $config->get('phonepe_user_id'),
    ];

    $form['phonepe_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe API Key'),
      '#default_value' => $config->get('phonepe_api_key'),
    ];

    $form['phonepe_pay_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Api URL'),
      '#default_value' => $config->get('phonepe_pay_url'),
    ];

    $form['phonepe_redirect_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL'),
      '#default_value' => $config->get('phonepe_redirect_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('phonepe_merchant_id', $form_state->getValue('phonepe_merchant_id'))
      ->set('phonepe_api_key', $form_state->getValue('phonepe_api_key'))
      ->set('phonepe_pay_url', $form_state->getValue('phonepe_pay_url'))
      ->set('phonepe_redirect_url', $form_state->getValue('phonepe_redirect_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}