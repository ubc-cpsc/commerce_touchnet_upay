<?php

namespace Drupal\commerce_touchnet_upay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class RedirectCheckoutForm.
 *
 * This defines a form that Drupal Commerce will redirect to, when the user
 * clicks the Pay and complete purchase button.
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // This method is responsible for:
    // building the array of data for the request to the payment provider
    // specifying the request method (POST or GET)
    // using buildRedirectForm() to submit the request to the payment provider

    // ... your custom code here ...

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();

    $data = [];

    $redirect_url = '';
    //$redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_post')->toString();

    // Example from https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/off-site-gateways/off-site-redirect
    // Replace/remove as needed.

    //$merchant_id = $configuration['merchant_id'];
    //$merchant_store_id = $configuration['merchant_store_id'];

    //// Payment gateway configuration data.
    //$data['version'] = 'v10';
    //$data['merchant_id'] = $configuration['merchant_id'];
    //$data['agreement_id'] = $configuration['agreement_id'];
    //$data['language'] = $configuration['language'];

    //// Payment data.
    //$data['currency'] = $payment->getAmount()->getCurrencyCode();
    //$data['total'] = $payment->getAmount()->getNumber();
    //$data['variables[payment_gateway]'] = $payment->getPaymentGatewayId();
    //$data['variables[order]'] = $payment->getOrderId();

    //// Order and billing address.
    //$order = $payment->getOrder();
    //$billing_address = $order->getBillingProfile()->get('address');
    //$data['name'] = $billing_address->getGivenName() . ' ' . $billing_address->getFamilyName();
    //$data['city'] = $billing_address->getLocality();
    //$data['state'] = $billing_address->getAdministrativeArea()

    //// Form url values.
    //$data['continueurl'] = $form['#return_url'];
    //$data['cancelurl'] = $form['#cancel_url'];

    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);
  }

}
