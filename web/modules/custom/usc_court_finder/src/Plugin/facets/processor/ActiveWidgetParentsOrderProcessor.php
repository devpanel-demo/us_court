<?php

namespace Drupal\usc_court_finder\Plugin\facets\processor;

use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by active state affecting parents.
 *
 * @FacetsProcessor(
 *   id = "active_widget_parents_order",
 *   label = @Translation("Sort by active state including parents"),
 *   description = @Translation("Sorts the widget results by active state affecting parents."),
 *   default_enabled = TRUE,
 *   stages = {
 *     "sort" = 20
 *   }
 * )
 */
class ActiveWidgetParentsOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface {

  use UnchangingCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {

    $a_active = $a->isActive() || $a->hasActiveChildren();
    $b_active = $b->isActive() || $b->hasActiveChildren();

    if ($a_active == $b_active) {
      return 0;
    }
    return $a_active ? -1 : 1;
  }

}
