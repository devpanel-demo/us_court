<?php

namespace Drupal\usc_vacancies\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to provide the current year.
 *
 * @\Drupal\views\Annotation\ViewsArgumentDefault(
 *   id = "currenty_year",
 *   title = @Translation("Current Year")
 * )
 */
class CurrentYear extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return date('Y');
  }

}
