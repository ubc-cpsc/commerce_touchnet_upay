<?php

namespace Drupal\commerce_touchnet_upay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
      'base_uri' => '',
      'merchant_id' => '',
      'merchant_store_id' => '',
      'merchant_proxy_key' => '',
      'merchant_update_secret' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['base_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The uPay Proxy API base URI.'),
      '#default_value' => $this->configuration['merchant_id'],
    ];
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
    ];
    $form['merchant_store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Merchant Store ID'),
      '#default_value' => $this->configuration['merchant_store_id'],
    ];
    $form['merchant_proxy_key'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#description' => 'This should be empty, override this config in your settings.',
      '#title' => $this->t('Your secret key'),
      '#default_value' => $this->configuration['merchant_proxy_key'],
    ];
    $form['merchant_update_secret'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#description' => 'This should be empty, override this config in your settings.',
      '#title' => $this->t('Your Merchant Store ID with uPay Proxy'),
      '#default_value' => $this->configuration['merchant_update_secret'],
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
      $this->configuration['base_uri'] = $values['base_uri'];
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
    // Intentionally left blank, all IPN will be handled via self::onNotify().
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    // @todo Replace if need a better message, or remove.
    parent::onCancel($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request):Response|null {
    // @todo Only create payment entities on successful payments.
    // 1. Request is validated
    // If validation fails: return new JsonResponse($exception->getMessage(), $exception->getCode());


    // @todo This method is responsible for:
    //
    //    performing verifications, throwing exceptions as needed
    //    creating and saving information to the Drupal Commerce payment for the order
    //
    // Typically, you will also want to log the information returned by the provider.
    //    This method should only be concerned with creating/completing payments.
    // @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/off-site-gateways/return-from-payment-provider#handling-payment-submission
    $resource = (object) [];
    $metadata = $resource->metadata;
    // @todo Add examples of request validation.
    // Note: Since requires_billing_information is FALSE, the order is
    // not guaranteed to have a billing profile. Confirm that
    // $order->getBillingProfile() is not NULL before trying to use it.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => new Price($resource->amount / 100, $resource->currency),
//      'amount' => $order->getBalance(),
      // 'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->parentEntity->id(),
//      'order_id' => $order->id(),
      'order_id' => $metadata['order_id'],
      'test' => $this->getMode() == 'test',
      'remote_id' => $request->query->get('txn_id'),
      'remote_state' => $request->query->get('payment_status'),
//      'remote_id' => $resource->id,
//      'remote_state' => empty($resource->failure) ? 'paid' : $resource->failure->code,
    ]);
    // $logger->info('Saving Payment information. Transaction reference: ' . $merchantTransactionReference);
    $payment->save();

    // drupal_set_message('Payment was processed');
    //
    //    $logger->info('Payment information saved successfully. Transaction reference: ' . $merchantTransactionReference);.
  }

}
