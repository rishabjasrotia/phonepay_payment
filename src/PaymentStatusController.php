<?php

namespace Drupal\phonepay_payment;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentStatusController {
  public function status($id) {
    $callback_url =  Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $id, 'step' => 'payment'], array('absolute' => true))->toString();
    return new RedirectResponse($callback_url);
  }
}