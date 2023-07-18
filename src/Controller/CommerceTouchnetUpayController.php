<?php

namespace Drupal\commerce_touchnet_upay\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Commerce TouchNet uPay routes.
 */
class CommerceTouchnetUpayController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    // @todo Add real controller responses and routing or delete.
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
