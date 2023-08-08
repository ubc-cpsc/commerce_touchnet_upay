# TouchNet uPay Proxy Payment Gateway.

UBC integration of TouchNet uPay with Commerce 2

# Install & Configure

1. Install this module as per your usual method.
2. Add a new payment gateway at `/admin/commerce/config/payment-gateways`, we've used `upay` for the machine name of the gateway.
3. We recommended to store many of the variables in an override in settings.php and don't store the keys and secrets in the database.
   1. Further in `.env` file outside the websites document root only if you can.
      ```
      UPAY_PROXY_API_URI='https://api.ubc.ca/upay/v1'
      UPAY_PROXY_MERCHANT_ID='MRCH'
      UPAY_PROXY_MERCHANT_STORE_ID='01'
      UPAY_PROXY_MERCHANT_PROXY_KEY='YOUR PROXY KEY'
      UPAY_PROXY_MERCHANT_UPDATE_SECRET='YOUR UPDATE SECRET'
      ```
      Overrides in `settings.php`
      ```
      /**
       * TouchNet uPay Proxy Payment Gateway override.
       */
      $config['commerce_payment.commerce_payment_gateway.upay']['configuration']['base_uri'] = getenv('UPAY_PROXY_API_URI');
      $config['commerce_payment.commerce_payment_gateway.upay']['configuration']['merchant_id'] = getenv('UPAY_PROXY_MERCHANT_ID');
      $config['commerce_payment.commerce_payment_gateway.upay']['configuration']['merchant_store_id'] = getenv('UPAY_PROXY_MERCHANT_STORE_ID');
      $config['commerce_payment.commerce_payment_gateway.upay']['configuration']['merchant_proxy_key'] = getenv('UPAY_PROXY_MERCHANT_PROXY_KEY');
      $config['commerce_payment.commerce_payment_gateway.upay']['configuration']['merchant_update_secret'] = getenv('UPAY_PROXY_MERCHANT_UPDATE_SECRET');
      ```


# Endpoints for DPP

You'll be asked for the endpoints and commerce will generate paths based on the machine name of the payment gateway.

For example: we named all of our Payment Gateway configs `upay` and our URLs look like this:

## Staging:

- Endpoint URL: https://stg-example.ubc.ca/payment/notify/upay
- Success Link URL: https://stg-example.ubc.ca/payment/success/upay
- Error Link URL: https://stg-example.ubc.ca/payment/error/upay
- Cancel Link URL: https://stg-example.ubc.ca/payment/cancel/upay

## Production:

- Endpoint URL: https://example.ubc.ca/payment/notify/upay
- Success Link URL: https://example.ubc.ca/payment/success/upay
- Error Link URL: https://example.ubc.ca/payment/error/upay
- Cancel Link URL: https://example.ubc.ca/payment/cancel/upay
