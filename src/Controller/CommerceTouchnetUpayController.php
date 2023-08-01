<?php

namespace Drupal\commerce_touchnet_upay\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Commerce TouchNet uPay routes.
 */
class CommerceTouchnetUpayController extends ControllerBase {

  /**
   * Builds the payment success response.
   */
  public function successPage() {
    // @todo Add real controller responses and routing or delete.
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Your payment was successful.'),
    ];

    return $build;
  }

  /**
   * Builds the payment error response.
   */
  public function errorPage() {
    // @todo Add real controller responses and routing or delete.
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('There was an error with your payment'),
    ];

    return $build;
  }

  /**
   * Builds the cancelled payment response.
   */
  public function cancelPage() {
    // @todo Add real controller responses and routing or delete.
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('You cancelled your payment'),
    ];

    return $build;
  }

}
