<?php

namespace Drupal\usc_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin formatter to render legacy D7 Tableau script fields.
 *
 * @FieldFormatter(
 *   id = "usc_tableau_script_formatter",
 *   label = @Translation("Tableau Formatter"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class TableauFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $script = $item->value;
      $format = $item->format;

      if (!empty($script) && !empty($format)) {
        $paragraph = $item->getEntity();
        $toolbar = $paragraph->get('field_tv_toolbar')->getString();
        $tabs = $paragraph->get('field_tv_tabs')->getString();

        $processed = $item->processed->__toString();

        // Generate a unique HTML ID based on a string.
        $html_id = Html::getUniqueId('tableau_viz');

        $processed = sprintf('<tableau-viz id="%s" src="%s" %s toolbar="%s"></tableau-viz>', $html_id, $processed, $tabs, $toolbar);

        // Build the render array for the theme.
        $elements[$delta] = [
          '#theme' => 'usc_tableau_script',
          '#script' => $processed,
          '#id' => $html_id,
          '#attached' => [
            'library' => [
              'usc_core/tableau'
            ],
          ],
        ];
      }
    }

    return $elements;
  }

}
