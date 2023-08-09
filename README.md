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

You'll be asked for the endpoints and commerce will generate paths based on the **machine name of the payment gateway**.

We named all of our Payment Gateway machine name as `upay` and the generated paths look like this:

- Endpoint Path: `/payment/notify/upay`
- Success Link Path: `/payment/success/upay`
- Error Link Path: `/payment/error/upay`
- Cancel Link Path: `/payment/cancel/upay`

Append the above paths on to your **Staging** or **Production** environment base URI:
## Example
Staging Endpoint URL: `https://stg-example.ubc.ca/payment/notify/upay`

# Caveats

* We have yet to implement (due to demand) a way to change any of the WorkDay overrides for a specific product, order, or store.
* There is a `refunded` workflow state and `refund` transition added to all workflows. There is no logic tied to that additional transition.
* We haven't added any other order statuses as was available in Drupal 7, like `pending`, we will keep it in `draft` to keep it simple for now.
* The `remote_id` is mapped to the value of the `uPayTrackingId`, currently not storing the `paymentGatewayReferenceNumber`
* We are not using `modes` like `live` or `test` because it's easier and less confusing to set that with the environment variables in `settings.php`.
* The **Success**, **Error**, and **Cancel** URLs are presentational only, you can replace the messaging or give a custom page if you prefer.

If you need any of the above please feel free to [request it](https://github.com/ubc-cpsc/commerce_touchnet_upay/issues/new).
