<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

/**
 * Court Useful Link Extra field interface.
 */
interface UsefulLinkInterface {

  /**
   * Returns a link label.
   *
   * @return string
   *   The link label.
   */
  public function getLabel(): string;

  /**
   * Returns a description.
   *
   * @return string
   *   The description.
   */
  public function getDescription(): string;

  /**
   * Returns a field name to get the link.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName(): string;

}
