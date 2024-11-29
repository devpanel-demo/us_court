<?php

namespace Drupal\usc_core;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * A handler for the Date field in the Data Table content type.
 */
class DataTableDateHandler implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['processDataTableDate'];
  }

  /**
   * Process a date field values.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $date
   *   The value of date.
   */
  public static function processDataTableDate(array &$element, FormStateInterface $form_state, $date): void {

    if (isset($element["month"])) {
      // Keep only months which are the end of a quarter.
      $callback = function ($month) {
        return $month % 3 === 0;
      };
      $element["month"]["#options"] = array_filter($element["month"]["#options"], $callback, ARRAY_FILTER_USE_KEY);

    }

    if (isset($element["day"])) {
      // Keep only the last two days.
      $callback = function ($day) {
        return $day == 30 || $day == 31;
      };
      $element["day"]["#options"] = array_filter($element["day"]["#options"], $callback, ARRAY_FILTER_USE_KEY);
    }

    if (isset($element["year"])) {
      // Keep year from 1990 to the current + 1;.
      $callback = function ($year) {
        return $year >= 1990 && $year <= date('Y') + 1;
      };
      $element["year"]["#options"] = array_filter($element["year"]["#options"], $callback, ARRAY_FILTER_USE_KEY);

    }

    $element['#attached']['library'][] = 'usc_core/data_tables_date';
  }

}
