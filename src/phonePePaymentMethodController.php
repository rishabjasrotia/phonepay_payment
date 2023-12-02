<?php

namespace Drupal\phonepe_payment;

use Drupal\Core\Routing\TrustedRedirectResponse;
use GuzzleHttp\ClientInterface;

/**
 * An PhonePe payment method.
 */
class PhonePePaymentMethodController {

  public function payment($paymentDetails = []) {

    $messenger = \Drupal::messenger();

    if (empty($paymentDetails)) {
      return;
    }

    $config = \Drupal::config('phonepe_payment.settings');

    // Phone Pay API credentials
    $phone_pay_merchant_id = $config->get('phonepe_merchant_id');
    $phone_pay_api_key = $config->get('phonepe_api_key');
    $phone_pay_api_url = $config->get('phonepe_pay_url');
    $phone_pay_redirect_url = $config->get('phonepe_redirect_url');
    $phone_pay_user_id = $config->get('phonepe_user_id');

    // Transaction details
    $amount = $paymentDetails['amount'];
    $currency = 'INR';
    $order_id = uniqid();
    $merhcant_trans_id = uniqid();
    $name = $paymentDetails['name'];
    $email = $paymentDetails['email'];
    $mobile = $paymentDetails['mobile'];
    $description  = $paymentDetails['description'];

    $paymentData = [
      'merchantId' => $phone_pay_merchant_id,
      'merchantTransactionId' => $merhcant_trans_id, // test transactionID
      "merchantUserId"=> $phone_pay_user_id,
      'amount' => ($amount * 100),
      'redirectUrl'=> $phone_pay_redirect_url,
      'redirectMode'=> "POST",
      'callbackUrl'=> $phone_pay_redirect_url,
      "merchantOrderId"=> $order_id,
      "mobileNumber"=> $mobile,
      "message"=> $description,
      "email"=> $email,
      "shortName"=> $name,
      "paymentInstrument"=> [
        "type"=> "PAY_PAGE",
      ]
    ];

    $jsonencode = json_encode($paymentData);
    $payloadMain = base64_encode($jsonencode);
    $salt_index = 1; //key index 1
    $payload = $payloadMain . "/pg/v1/pay" . $phone_pay_api_key;
    $sha256 = hash("sha256", $payload);
    $final_x_header = $sha256 . '###' . $salt_index;
    $request = json_encode(['request'=> $payloadMain]);

    // Send the request to Phone Pay API
    $httpClient = \Drupal::httpClient();
    $response  = $httpClient->request('POST', $phone_pay_api_url, $request);
    $response_payload = json_decode($response->getBody()->getContents(), TRUE);

    // Process the response from Phone Pay API
    if ($response_data) {
      if ($response_payload['success'] == '1') {
        // Payment success
        $paymentCode= $response_payload['code'];
        $paymentMsg= $response_payload['message'];
        $payUrl= $response_payload['data']->instrumentResponse->redirectInfo->url;
        return new TrustedRedirectResponse($payUrl);
        // Redirect the to Pay URL
      } else {
        // Payment failed
        $message = $response_payload['error_message'];
        // Display the error message to the user
      }
    } else {
      // Error occurred while communicating with Phone Pay API
      $message = 'Error occurred while communicating with Phone Pay API';
      // Display the error message to the user
    }

    $messenger->addMessage(t($error_message));

  }
}