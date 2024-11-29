<?php

namespace Drupal\usc_failtest\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for viewing failtest.
 */
class FailTestPageController extends ControllerBase {

  /**
   * Failtest page.
   */
  public function failtest() {

    $config = $this->config('usc_failtest.settings');

    // Get the failtest message (or a default if it is empty).
    $message = $config->get('failtest_message');
    $server = $config->get('failtest_server');

    // Return the message as part of a renderable array.
    return [
      '#markup' => $message . '<br />' . $server,
      '#cache' => [
        'tags' => [
          'config:usc_failtest.settings'
        ],
      ],
    ];
  }

}
