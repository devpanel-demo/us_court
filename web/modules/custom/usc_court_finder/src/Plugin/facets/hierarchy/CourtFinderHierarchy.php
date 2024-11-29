<?php

namespace Drupal\usc_court_finder\Plugin\facets\hierarchy;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\Hierarchy\HierarchyPluginBase;
use Drupal\facets\Plugin\facets\hierarchy\Taxonomy;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the CourtFinderHierarchy hierarchy class.
 *
 * @FacetsHierarchy(
 *   id = "usc_court_finder_taxonomy",
 *   label = @Translation("Court FinderTaxonomy hierarchy"),
 *   description = @Translation("Hierarchy structure for USC Court Finder locations.")
 * )
 */
class CourtFinderHierarchy extends Taxonomy {

  /**
   * {@inheritdoc}
   */
  public function getNestedChildIds($id): array {

    $term = $this->getTermStorage()->load($id);
    // We do not need go deeper than the circuit or state levels.
    if ($term->field_usc_type != 'circuit' || $term->field_usc_type != 'state') {
      return [];
    }

    if (isset($this->nestedChildren[$id])) {
      return $this->nestedChildren[$id];
    }

    $children = $this->getTermStorage()->loadChildren((int) $id);
    $children = array_filter(array_values(array_map(function ($it) {
      return $it->id();
    }, $children)));

    $subchilds = [];
    /** @var \Drupal\taxonomy\TermInterface $child */
    foreach ($children as $child) {
      $subchilds = array_merge($subchilds, $this->getNestedChildIds($child->id()));
    }
    return $this->nestedChildren[$id] = array_merge($children, $subchilds);
  }

}
