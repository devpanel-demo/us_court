<?php

declare(strict_types=1);

namespace Drupal\usc_core\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Adds a text filter to replace ol style to ol class.
 *
 * @Filter(
 *   id = "list_style_to_class",
 *   title = @Translation("List style to Class"),
 *   description = @Translation("Updates ol style list-style-type to ol class"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
final class ListStyleToClass extends FilterBase {

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

    $lists = $source->getElementsByTagName('ol');

    // Iterate each $list.
    foreach ($lists as $list) {
      $style = $list->getAttribute('style');

      if ($style) {
        // Define the regex pattern.
        $pattern = '/list-style-type:([^";]+)/';

        // If we find the list-style-type style we add a class.
        if (preg_match($pattern, $style, $matches)) {
          $list->setAttribute('class', 'list-style-type-' . $matches[1]);
        }
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
