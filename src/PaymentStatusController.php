<?php

namespace Drupal\phonepay_payment;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\Order;

class PaymentStatusController  {

  public function status($order_id) {
    $order = Order::load($order_id);
    if($order->getState()->getId() == 'completed') {
      $url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $order->id(), 'step' => $step_id]);
      return new RedirectResponse($url->toString());
    } else {
      return [
        '#markup' => 'Payment Pending or Failed! Please try again or refresh this page.'
      ];
    }
  }

  public function callback($order_id) {
    $serverCallback = json_decode(\Drupal::request()->getContent(), TRUE); 
    if (!empty($serverCallback['response'])) {
      $payload = json_decode(base64_decode($serverCallback['response']), true);
      if ($payload['success'] && $payload['code'] == 'PAYMENT_SUCCESS') {
        // Payment Done update order details.
        $order = Order::load($order_id);
        /** @var PaymentGateway $paymentGateway */
        $paymentGateway = $order->get('payment_gateway')->entity;
        $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');

        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $paymentGateway->id(),
          'order_id' => $order->id(),
          'remote_id' => $order->id(),
          'remote_state' => $payload['code'],
        ]);
        $payment->save();
        \Drupal::messenger()->addMessage(t('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id', ['@orderid' => $order->id(), '@transaction_id' => $txnid]));
      }
    }

    return [
      'success' => $response,
    ];
  }
}