<?php

namespace Drupal\commerce_touchnet_upay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RedirectCheckoutForm.
 *
 * This defines a form that Drupal Commerce will redirect to, when the user
 * clicks the Pay and complete purchase button.
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * Separator between order id and suffix
   */
  public const ORDER_ID_SUFFIX_SEPARATOR = '_';

    /**
   * Build a redirect URL to UBC uPay Proxy with the payment request data.
   *
   * Build an array of data for the request to the payment provider
   * specifying the request method (POST or GET)
   * using buildRedirectForm() to submit the request to the payment provider.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();

    // Build redirect URL.
    $redirect_url = $configuration['base_uri'] . '/payment-request';

    // Payment gateway configuration data.
    $data = [];

    // Merchant details.
    $data['merchantId'] = $configuration['merchant_id'];
    $data['merchantStoreId'] = $configuration['merchant_store_id'];

    // Payment details.
    // Make paymentRequestNumber unique by appending a separator and current
    // Unix timestamp to order id because TouchNet rejects any previously used
    // payment request number.
    $data['paymentRequestNumber'] = $payment->getOrderId() . self::ORDER_ID_SUFFIX_SEPARATOR . time();
    $data['paymentRequestAmount'] = $payment->getAmount()->getNumber();

    // Build proxy hash seed to verify the payment request.
    $proxyHashSeed = $configuration['merchant_proxy_key'] . $data['paymentRequestNumber'] . $data['paymentRequestAmount'];
    $data['proxyHash'] = base64_encode(md5($proxyHashSeed, TRUE));

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);
  }

}
