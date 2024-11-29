<?php

namespace Drupal\usc_court_finder\Plugin\facets\processor;

use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\QueryHelper;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A processor that orders the results by distance from the origin place.
 *
 * @FacetsProcessor(
 *   id = "usc_district_filter",
 *   label = @Translation("Exclude State and other terms"),
 *   description = @Translation("Filter the location terms."),
 *   default_enabled = FALSE,
 *   stages = {
 *     "build" = 101
 *   }
 * )
 */
class DistrictFilterProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

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
   * @param \Drupal\search_api\Utility\QueryHelper $queryHelper
   *   The search API query helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
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
      $container->get('search_api.query_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {

    // Order elements first so we can use only 14 closest elements and parents.

    $max_distance = 10000;
    $query_results = $this->queryHelper->getResults('views_page:usc_court_finder_locations__page_1');
    $query = $query_results->getQuery();
    $options = $query->getOptions();
    if (!empty($options['search_api_location'][0])) {
      $location = $options['search_api_location'][0];
      $lat = $location['lat'];
      $lon = $location['lon'];
      $view_id = 'usc_district_distance_sorting';
      $view_display = 'attachment_1';
      $view = Views::getView($view_id);
      $view->setDisplay($view_display);
      $view->preExecute();
      $args = [];
      // Set a contextual filter.
      $args[] = "$lat,$lon<={$max_distance}mi";
      $view->setArguments($args);
      $view->execute();
      $view_results = $view->result;
    }
    foreach ($results as $id => $result) {
      // Get second level items.
      $state_districts = [];
      $states = $result->getChildren();
      // Evaluate if circuit has at least one of the 14 closest districts or remove.
      $closest_districts = FALSE;
      foreach ($states as $key => $state) {
        $districts = $state->getChildren();
        foreach ($districts as $district_key => $district) {
          if ($result->isActive()) {
            $district->setActiveState(TRUE);
          }
          $closest_district_flag = FALSE;
          if (!empty($view_results)) {
            foreach ($view_results as $key => $closest_district) {
              if (property_exists($closest_district, 'tid')) {
                if ($district->getRawValue() === $closest_district->tid) {
                  $closest_districts = TRUE;
                  $closest_district_flag = TRUE;
                }
              }
            }
            $url = $district->getUrl();
            if ($closest_district_flag == FALSE && !$district->isActive()) {
              $url->setOption('attributes', ['class' => ['fcf-secondary-option', 'collapsed']]);
            }
            else {
              $url->setOption('attributes', ['class' => ['fcf-primary-option']]);
            }
            $district->setUrl($url);
          }
          $state_districts[] = $district;
        }
      }
      if ($closest_districts == FALSE) {
        unset($results[$id]);
      }
      else {
        $results[$id]->setChildren($state_districts);
      }
    }
    return $results;
  }

}
