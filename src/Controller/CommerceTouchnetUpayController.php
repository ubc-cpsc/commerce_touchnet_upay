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

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
