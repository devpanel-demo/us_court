<?php

namespace Drupal\usc_court_finder\Plugin\facets\processor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Result\Result;
use Drupal\search_api\Utility\QueryHelper;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A processor that orders the results by distance from the origin place.
 *
 * @FacetsProcessor(
 *   id = "usc_distance_widget_order",
 *   label = @Translation("Sort by distance"),
 *   description = @Translation("Sorts the widget results by distance from the origin place."),
 *   default_enabled = FALSE,
 *   stages = {
 *     "sort" = 50
 *   }
 * )
 */
class DistanceWidgetOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface, ContainerFactoryPluginInterface {

  use UnchangingCacheableDependencyTrait;

  /**
   * Constructs a DistanceWidgetOrderProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\search_api\Utility\QueryHelper $queryHelper
   *   The search API query helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected CacheBackendInterface $cache,
    protected QueryHelper $queryHelper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.data'),
      $container->get('search_api.query_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {
    return $this->getDistance($a) <=> $this->getDistance($b);
  }

  /**
   * Provides a distance for a facet result.
   *
   * @param \Drupal\facets\Result\Result $result
   *   A facet result.
   *
   * @return float
   *   A distance for a facet result.
   */
  private function getDistance(Result $result): float {

    // 10k miles is used as a filter by proximity in the search view as well.
    // The greatest distance between any two points in U.S. territory:
    // 9,514 miles (15,311 km) from Point Udall, Guam,
    // to Point Udall, St. Croix, U.S. Virgin Islands.
    $max_distance = 10000;

    // It is tricky how it works but it is the best way I found so far to
    // extract the user entered location for Court Finder.
    $result_id = (int) $result->getRawValue();
    $source = $result->getFacet()->getFacetSource();
    if ($source instanceof SearchApiFacetSourceInterface) {
      $display = $source->getDisplay();
    }
    else {
      return $max_distance;
    }
    $search_id = $display->getPluginId();
    $results = $this->queryHelper->getResults($search_id);
    $query = $results->getQuery();
    $options = $query->getOptions();

    // The search may come up w/o a location.
    if (empty($options['search_api_location'][0])) {
      return $max_distance;
    }

    $location = $options['search_api_location'][0];
    $lat = $location['lat'];
    $lon = $location['lon'];

    $cid = "usc_court_finder:distances:$lat:$lon";

    if ($cache = $this->cache->get($cid)) {
      $distances = $cache->data;
    }
    else {

      // An aux view is used to get distances for districts with a state and
      // a circuit ids.
      // The view id is hardcoded for now but we can use a facet config if we
      // need more flexibility.
      $view_id = 'usc_district_distance_sorting';
      $view_display = 'default';
      $view = Views::getView($view_id);
      $view->setDisplay($view_display);
      $view->preExecute();
      $args = [];

      // Set a contextual filter.
      $args[] = "$lat,$lon<={$max_distance}mi";
      $view->setArguments($args);
      $view->execute();
      $view_results = $view->result;

      $distances = [];

      // Form and cache a hash map with distances for circuits, states and
      // districts to reuse it for all curent results.
      foreach ($view_results as $view_result) {
        if (property_exists($view_result, 'tid')) {
          $disrict_id = $view_result->tid;
        }
        if (property_exists($view_result, 'taxonomy_term_field_data_taxonomy_term__parent_tid')) {
          $state_id = $view_result->taxonomy_term_field_data_taxonomy_term__parent_tid;
        }
        if (property_exists($view_result, 'taxonomy_term_field_data_taxonomy_term__parent_1_tid')) {
          $circuit_id = $view_result->taxonomy_term_field_data_taxonomy_term__parent_1_tid;
        }
        if (property_exists($view_result, 'taxonomy_term__field_usc_coordinates_field_usc_coordinates_p')) {
          $distance = $view_result->taxonomy_term__field_usc_coordinates_field_usc_coordinates_p;
        }

        if (isset($distance)) {
          if (isset($disrict_id) && !array_key_exists($disrict_id, $distances)) {
            $distances[$disrict_id] = $distance;
          }
          if (isset($state_id) && !array_key_exists($state_id, $distances)) {
            $distances[$state_id] = $distance;
          }
          if (isset($circuit_id) && !array_key_exists($circuit_id, $distances)) {
            $distances[$circuit_id] = $distance;
          }
        }
      }

      $this->cache->set($cid, $distances, time() + 3600);
    }

    return array_key_exists($result_id, $distances) ? $distances[$result_id] : $max_distance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sort' => 'ASC',
    ];
  }

}
