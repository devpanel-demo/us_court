<?php

declare(strict_types=1);

namespace Drupal\usc_core\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Adds a text filter to replace ol style to ol class.
 *
 * @Filter(
 *   id = "table_class",
 *   title = @Translation("Table USWDS Class"),
 *   description = @Translation("Updates table uswds class"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
final class TableClass extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    if (empty($text)) {
      return new FilterProcessResult($text);
    }

    // Load the HTML as a DOM document to traverse through.
    $source = new \DOMDocument('5.0', 'UTF-8');
    try {
      libxml_use_internal_errors(TRUE);
      $html_entities = htmlentities($text, ENT_COMPAT, 'UTF-8');
      $iconv = iconv('UTF-8', 'ISO-8859-1//IGNORE', $html_entities);
      if (is_string($iconv)) {
        $html = htmlspecialchars_decode($iconv, ENT_QUOTES);
        $source->loadHTML($html);
      }
    }
    catch (\Exception) {
    }
    $source->encoding = 'utf-8';

    $lists = $source->getElementsByTagName('table');

    // Iterate each $list.
    foreach ($lists as $list) {
      $class = $list->getAttribute('class');
      if (!str_contains($class, 'usa-table')) {
        $list->setAttribute('class', $class . ' usa-table');
      }
    }

    $result = preg_replace('/<!DOCTYPE .*>/', '', $source->saveHTML());
    $result = preg_replace('/<html><body>/', '', $result);
    $result = preg_replace('/<\/body><\/html>/', '', $result);

    return new FilterProcessResult($result);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE): string {
    return (string) $this->t('@todo Provide filter tips here.');
  }

}
