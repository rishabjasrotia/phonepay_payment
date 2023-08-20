<?php

namespace Drupal\phonepe_payment;

use GuzzleHttp\ClientInterface;

/**
 * An PhonePe payment method.
 */
class PhonePePaymentMethodController {

  public function payment() {
    // Phone Pay API credentials
    $phone_pay_merchant_id = 'your_merchant_id';
    $phone_pay_secret_key = 'your_secret_key';

    // Transaction details
    $amount = 100;
    $currency = 'INR';
    $transaction_id = uniqid();

    // Phone Pay API endpoint
    $phone_pay_url = 'https://api.phonepay.io/v1/charge';

    // Prepare the request payload
    $request_payload = [
      'merchant_id' => $phone_pay_merchant_id,
      'transaction_id' => $transaction_id,
      'amount' => $amount,
      'currency' => $currency
    ];

    // Generate the request signature
    $signature = hash_hmac('sha256', json_encode($request_payload), $phone_pay_secret_key);

    // Add the signature to the request payload
    $request_payload['signature'] = $signature;

    // Send the request to Phone Pay API
    $httpClient = \Drupal::httpClient();
    $response  = $httpClient->request('POST', $phone_pay_url, $request_payload);
    $response_payload = json_decode($response->getBody()->getContents(), TRUE);

    // Process the response from Phone Pay API
    if ($response_data) {
      if ($response_payload['status'] == 'success') {
        // Payment success
        $transaction_reference = $response_payload['transaction_reference'];
        // Save the transaction details to your database
        // Redirect the user to a success page
      } else {
        // Payment failed
        $error_message = $response_payload['error_message'];
        // Display the error message to the user
      }
    } else {
      // Error occurred while communicating with Phone Pay API
      $error_message = 'Error occurred while communicating with Phone Pay API';
      // Display the error message to the user
    }


  }
}