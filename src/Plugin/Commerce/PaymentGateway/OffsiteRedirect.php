<?php

namespace Drupal\commerce_touchnet_upay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the off-site payment gateway for UBC TouchNet uPay proxy.
 *
 * @CommercePaymentGateway(
 *   id = "touchnet_upay_offsite_redirect",
 *   label = @Translation("UBC Touchnet uPay Proxy (Off-site redirect)"),
 *   display_label = @Translation("UBC Touchnet uPay Proxy"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_touchnet_upay\PluginForm\PaymentOffsiteForm",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase implements OffsitePaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'merchant_store_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Merchant ID with uPay Proxy'),
      '#default_value' => $this->configuration['merchant_id'],
    ];
    $form['merchant_store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Merchant Store ID with uPay Proxy'),
      '#default_value' => $this->configuration['merchant_store_id'],
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
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['merchant_store_id'] = $values['merchant_store_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifyUrl(): Url {
    return Url::fromRoute('commerce_payment.notify', [
      'commerce_payment_gateway' => $this->parentEntity->id(),
    ], ['absolute' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // @todo Add examples of request validation.
    // Note: Since requires_billing_information is FALSE, the order is
    // not guaranteed to have a billing profile. Confirm that
    // $order->getBillingProfile() is not NULL before trying to use it.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => $order->getBalance(),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $request->query->get('txn_id'),
      'remote_state' => $request->query->get('payment_status'),
    ]);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $this->messenger()->addMessage($this->t('You have canceled checkout at @gateway but may resume the checkout process here when you are ready.', [
      '@gateway' => $this->getDisplayLabel(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {

  }

}
