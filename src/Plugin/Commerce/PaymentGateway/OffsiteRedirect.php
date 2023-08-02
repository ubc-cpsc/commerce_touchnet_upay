<?php

namespace Drupal\commerce_touchnet_upay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Render\FormattableMarkup;
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
   *
   * This method is responsible for:
   *  - Performing verifications, throwing exceptions as needed.
   *  - Creating and saving information to the Commerce payment for an order.
   */
  public function onNotify(Request $request):Response|null {
    // Show nothing if anonymous user accesses this
    // /payment/notify/{commerce_payment_gateway} page.
    if ($request->getMethod() === 'GET') {
      throw new NotFoundHttpException();
    }

    $logger = \Drupal::logger('commerce_touchnet_upay');
    // Only create payment entities on successful payments.

    // 1. Request is validated
    // If validation fails: return new JsonResponse($exception->getMessage(), $exception->getCode());

    // 1. Verify this matches:'merchantUpdateSecret'
    // 2.paymentStatus == success, else cancelled = cancel order?
    // 3. paymentAmount matches order total
    $chargedAmount = $transactionData['charged_amount'];
    $orderAmount = $order->getTotalPrice()->getNumber();
    if ($orderAmount != $chargedAmount) {
      $logger->warning('Charged Amount is: ' . $chargedAmount . ' while Order Amount: ' . $orderAmount);
      throw new PaymentGatewayException('Charged amount not equal to order amount.');
    }

    // Data available after validation:
    //    merchantUpdateSecret
    //    paymentRequestNumber
    //    paymentStatus
    //    paymentAmount
    //    paymentDate
    //    paymentType
    //    paymentCardType
    //    uPayTrackingId (order id?)
    //    paymentGatewayReferenceNumber (remote id)

    // Valid Responses:
    //  200	Payment status update processed successfully
    //  400	Payment status update request does not comply with API specifications
    //  401	Invalid credentials supplied with the update request
    //  404	Incorrect URL for Payment status update posting
    //  500	A Merchant Web site processing error
    //  503	Service Unavailable


    // Log the response message if request logging is enabled.
    // @todo Log only when requested.
    if (TRUE || !empty($this->configuration['api_logging'])) {
      \Drupal::logger('commerce_touchnet_upay')
        ->debug('e-Commerce notification: <pre>@body</pre>', [
          '@body' => var_export($request->query->all(), TRUE),
        ]);
    }

    // Typically, you will also want to log the information returned by the provider.
    //    This method should only be concerned with creating/completing payments.
    // @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/off-site-gateways/return-from-payment-provider#handling-payment-submission
    $resource = (object) [];
    $metadata = $resource->metadata;

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',

      // @todo use getCurrencyCode() or get from db
      'amount' => new Price($resource->amount / 100, 'CAD'),
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
