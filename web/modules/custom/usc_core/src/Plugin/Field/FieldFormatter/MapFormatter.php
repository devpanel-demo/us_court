<?php

namespace Drupal\usc_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin formatter to render legacy D7 map script fields.
 *
 * @FieldFormatter(
 *   id = "usc_map_script_formatter",
 *   label = @Translation("Interactive Map Formatter"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class MapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $script = $item->value;
      $format = $item->format;

      if (!empty($script) && !empty($format)) {
        $processed = $item->processed->__toString();

        // Generate a unique HTML ID based on a string.
        $html_id = Html::getUniqueId('usc_map');

        // Build the render array for the theme.
        $elements[$delta] = [
          '#theme' => 'usc_map_script',
          '#script' => $processed,
          '#id' => $html_id,
          '#attached' => [
            'library' => [
              'usc_core/map'
            ],
          ],
        ];
      }
    }

    return $elements;
  }

}
