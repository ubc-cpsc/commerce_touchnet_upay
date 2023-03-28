<?php

namespace Drupal\commerce_touchnet_upay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the off-site payment gateway for UBC TouchNet uPay proxy.
 *
 * @CommercePaymentGateway(
 *   id = "touchnet_upay_redirect_checkout",
 *   label = @Translation("UBC Touchnet uPay Proxy (Off-site redirect)"),
 *   display_label = @Translation("UBC Touchnet uPay Proxy"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_touchnet_upay\PluginForm\RedirectCheckoutForm",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'server' => '',
      'site_id' => '',
      'success_link_text' => '',
      'continue_link_text' => '',
      'error_link_text' => '',
      'cancel_link_text' => '',
      'validation_key' => '',
      'credit_acct_code' => '',
      'debit_acct_code' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TouchNet Server'),
      '#default_value' => $this->configuration['server'],
    ];
    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => $this->configuration['site_id'],
    ];
    $form['validation_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Validation key'),
      '#default_value' => $this->configuration['validation_key'],
    ];
    $form['credit_acct_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Credit Account Code (Override)'),
      '#default_value' => $this->configuration['credit_acct_code'],
    ];
    $form['debit_acct_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Debit Account Code (Override)'),
      '#default_value' => $this->configuration['debit_acct_code'],
    ];
    $form['success_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text For Success Link'),
      '#default_value' => $this->configuration['success_link_text'],
    ];
    $form['continue_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text For Continue Link'),
      '#default_value' => $this->configuration['continue_link_text'],
    ];
    $form['error_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text For Error Link'),
      '#default_value' => $this->configuration['error_link_text'],
    ];
    $form['cancel_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text For Cancel Link'),
      '#default_value' => $this->configuration['cancel_link_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['server'] = $values['server'];
      $this->configuration['site_id'] = $values['site_id'];
      $this->configuration['success_link_text'] = $values['success_link_text'];
      $this->configuration['continue_link_text'] = $values['continue_link_text'];
      $this->configuration['error_link_text'] = $values['error_link_text'];
      $this->configuration['cancel_link_text'] = $values['cancel_link_text'];
      $this->configuration['validation_key'] = $values['validation_key'];
      $this->configuration['credit_acct_code'] = $values['credit_acct_code'];
      $this->configuration['debit_acct_code'] = $values['debit_acct_code'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    return parent::onNotify($request);
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {

  }

}
