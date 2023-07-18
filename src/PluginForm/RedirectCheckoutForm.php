<?php

namespace Drupal\commerce_touchnet_upay\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RedirectCheckoutForm.
 *
 * This defines a form that Drupal Commerce will redirect to, when the user
 * clicks the Pay and complete purchase button.
 */
class RedirectCheckoutForm extends PaymentOffsiteForm {

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
    $redirect_url = '';
    $data = [];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);
  }

}
