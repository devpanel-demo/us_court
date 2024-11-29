<?php

namespace Drupal\piksel\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PikselProvider annotation object.
 *
 * @Annotation
 */
class PikselProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var string
   */
  public $label;

}
