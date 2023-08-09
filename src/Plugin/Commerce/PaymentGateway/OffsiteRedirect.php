<?php

namespace Drupal\commerce_touchnet_upay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the off-site payment gateway for UBC TouchNet uPay proxy.
 *
 * @CommercePaymentGateway(
 *   id = "touchnet_upay_offsite_redirect",
 *   label = @Translation("UBC Touchnet uPay Proxy (Off-site redirect)"),
 *   display_label = @Translation("UBC Touchnet uPay Proxy"),
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
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
      'debug_log' => '',
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
      '#description' => 'This should be empty, override this config in your settings.php.',
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
    $form['debug_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log the notification request while debugging?'),
      '#default_value' => $this->configuration['debug_log'],
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
      $this->configuration['debug_log'] = $values['debug_log'];
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
    $order->getState()->applyTransitionById('cancel');
    $order->save();

    $this->messenger()->addMessage($this->t('You have canceled checkout at @gateway but may resume the checkout process here when you are ready.', [
      '@gateway' => $this->getDisplayLabel(),
    ]));
  }

  /**
   * {@inheritdoc}
   *
   * Only create payment entities on successful payments.
   * This method is responsible for:
   *  - Performing verifications, throwing exceptions as needed.
   *  - Creating and saving information to the Commerce payments.
   *
   * Data available after validation:
   * - merchantUpdateSecret
   * - paymentRequestNumber
   * - paymentStatus
   * - paymentAmount
   * - paymentDate
   * - paymentType
   * - paymentCardType
   * - uPayTrackingId: remote id
   * - paymentGatewayReferenceNumber: credit card transaction id
   *
   * Valid Responses:
   * 200 Payment status update processed successfully.
   * 400 Payment status update request does not comply with API specs.
   * 401 Invalid credentials supplied with the update request.
   * 404 Incorrect URL for Payment status update posting.
   * 500 A Merchant Web site processing error.
   * 503 Service Unavailable.
   */
  public function onNotify(Request $request):Response|null {
    // Show nothing if anonymous user accesses this
    // /payment/notify/{commerce_payment_gateway} page.
    if ($request->getMethod() === 'GET') {
      throw new NotFoundHttpException();
    }

    // Get IPN request data.
    $data = $this->getRequestDataArray($request->getContent());
    $logger = \Drupal::logger('commerce_touchnet_upay');

    // Log the response message if request logging is enabled.
    if (!empty($this->configuration['debug_log'])) {
      $logger->debug('uPay OffsiteRedirect::onNotify() data: <pre>@data</pre>', [
        '@data' => var_export($data, TRUE),
      ]);
    }

    // 1. Verify this matches:'merchantUpdateSecret'
    if (empty($data['merchantUpdateSecret']) || $data['merchantUpdateSecret'] !== $this->configuration['merchant_update_secret']) {
      $logger->warning('Merchant Update Secret does not match');
      return new Response('Unauthorized to update the transaction', Response::HTTP_UNAUTHORIZED);
    }

    // 2. Check if the order can be loaded from the 'paymentRequestNumber'.
    $order_id = $data['paymentRequestNumber'] ?? 0;
    /** @var \Drupal\commerce_order\OrderStorageInterface $order_storage */
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($order_id);
    if (!$order) {
      $logger->warning('Invalid order id from uPay proxy paymentRequestNumber notification: %order_id', [
        '%order_id' => $order_id,
      ]);
      throw new PaymentGatewayException('Invalid order number');
    }

    // 3. When 'paymentStatus' is not 'success' Cancel the order.
    if (empty($data['paymentStatus']) || $data['paymentStatus'] !== 'success') {
      $logger->warning('Order cancelled');
      $this->onCancel($order, $request);
      return NULL;
    }

    // 4. Compare 'paymentAmount' matches order total.
    $charged_amount = (float) $data['paymentAmount'] ?? NULL;
    $order_amount = (float) $order->getTotalPrice()->getNumber();
    if ($order_amount !== $charged_amount) {
      $logger->warning('Charged Amount is: %charged_amount while Order Amount: %order_amount', [
        '%charged_amount' => $charged_amount,
        '%order_amount' => $order_amount,
      ]);
      throw new PaymentGatewayException('Charged amount is not equal to order amount.');
    }

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $data['uPayTrackingId'],
      'remote_state' => $data['paymentStatus'],
    ]);

    try {
      $payment->save();
    }
    catch (\Exception $e) {
      $logger->error($this->t('Payment Save Failed! Order ID# @order_id', ['@order_id' => $order->id()]) . $e->getMessage());
    }

    return NULL;
  }

  /**
   * Get data array from a request content.
   *
   * @param string $request_content
   *   The Request content.
   *
   * @return array
   *   The request data array.
   */
  protected function getRequestDataArray($request_content) {
    parse_str(html_entity_decode(trim($request_content)), $ipn_data);
    return $ipn_data;
  }

}
