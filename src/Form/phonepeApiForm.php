<?php

namespace Drupal\phonepe_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class phonepeApiForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'phonepe_payment.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phonepe_payment_settings';
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

    $form['phonepe_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Secret Key'),
      '#default_value' => $config->get('phonepe_secret_key'),
    ];

    $form['phonepe_pay_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhonePe Api URL'),
      '#default_value' => $config->get('phonepe_pay_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('phonepe_merchant_id', $form_state->getValue('phonepe_merchant_id'))
      ->set('phonepe_secret_key', $form_state->getValue('phonepe_secret_key'))
      ->set('phonepe_pay_url', $form_state->getValue('phonepe_pay_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}