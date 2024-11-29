<?php

namespace Drupal\usc_court_finder\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Court Finder event subscriber.
 */
class CourtFinderSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CourtFinderSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Converts the default filter to real values.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($path === '/federal-court-finder/find' && $request->query->get('filter') === 'default') {
      $params = $request->query->all();

      $district_ids = [];

      if (!empty($params['country'])) {

        // Overseas territories.
        if (in_array($params['country'], ['PR', 'GU', 'VI', 'MP'])) {
          $district_ids = $this->districtsByStateCode($params['country']);
        }
        elseif ($params['country'] === 'US') {
          // Statewide searches.
          if (empty($params['county']) && empty($params['zip']) && !empty($params['state'])) {
            $district_ids = $this->districtsByStateCode($params['state']);
          }
          // The District of Columbia.
          elseif (!empty($params['state']) && $params['state'] === 'DC') {
            $district_ids = $this->districtsByStateCode($params['state']);
          }
          // Geocode data did not provide a "county" value.
          elseif (empty($params['county']) && !empty($params['zip'])) {
            $district = $this->districtByZip($params['zip']);
            if ($district) {
              $district_ids[] = $district;
            }
          }
          // All other locations.
          elseif (!empty($params['county']) && !empty($params['state'])) {
            $district = $this->districtByCountyAndState($params['county'], $params['state']);
            if ($district) {
              $district_ids[] = $district;
            }
          }
        }
      }

      // Form a redirect url to the Court Finder with default filters.
      $options = [];
      // Unset a 'filter' query parameter to avoid an infinite reqirect loop.
      unset($params['filter']);

      foreach ($district_ids as $district_id) {
        $params['f'][] = "usc_district_id:$district_id";
      }

      $options['query'] = $params;

      $court_finder_url = Url::fromUserInput($path, $options)->toString();
      // Redirect user to the Court Finder page.
      $response = new RedirectResponse($court_finder_url);
      $event->setResponse($response);

    }

  }

  /**
   * Look up a district by county/state.
   *
   * @param string $county
   *   A county.
   * @param string $state_code
   *   A state code.
   *
   * @return int|null
   *   A district id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function districtByCountyAndState(string $county, string $state_code): int|null {
    /** @var \Drupal\taxonomy\TermStorage $storage */
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties(['name' => $county]);

    if ($terms) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      foreach ($terms as $term) {
        $parents = $storage->loadAllParents($term->id());
        $district = NULL;
        /** @var \Drupal\taxonomy\TermInterface $parent */
        foreach ($parents as $parent) {
          if ($parent->get('field_usc_type')->value === 'district') {
            $district = $parent->id();
          }
          elseif ($parent->get('field_usc_type')->value === 'state' && $parent->get('field_usc_code')->value === $state_code && $district) {
            return $district;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Look up districts by circuit.
   *
   * @param string $circuit_id
   *   A circuit id.
   *
   * @return array
   *   Districts
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function districtsByCircuitId(string $circuit_id): array {
    $results = [];
    /** @var \Drupal\taxonomy\TermStorage $storage */
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $storage->load($circuit_id);

    if ($term) {
      $states = $storage->getChildren($term);
      /** @var \Drupal\taxonomy\TermInterface $state */
      foreach ($states as $state) {
        if ($state) {
          /** @var \Drupal\taxonomy\TermInterface $district */
          $districts = $storage->getChildren($state);
          foreach ($districts as $district) {
            $results[] = $district->id();
          }
        }
      }
    }
    return $results;
  }

  /**
   * Look up all districts that match a state code.
   *
   * @param string $state_code
   *   The state code.
   *
   * @return array
   *   A list of district ids
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function districtsByStateCode(string $state_code): array {
    $district_ids = [];
    /** @var \Drupal\taxonomy\TermStorage $storage */
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties(['field_usc_code' => $state_code]);

    if ($terms) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = reset($terms);
      $children = $storage->getChildren($term);
      /** @var \Drupal\taxonomy\TermInterface $child */
      foreach ($children as $child) {
        if ($child->get('field_usc_code')->value) {
          $district_ids[] = $child->id();
        }
      }
    }

    return $district_ids;
  }

  /**
   * Look up a dictrict from a zip code.
   *
   * @param string $zip
   *   A postal code.
   *
   * @return string|null
   *   A district id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function districtByZip(string $zip): string|null {
    /** @var \Drupal\taxonomy\TermStorage $storage */
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties(['name' => $zip]);

    if ($terms) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = reset($terms);
      $parents = $storage->loadAllParents($term->id());
      /** @var \Drupal\taxonomy\TermInterface $parent */
      foreach ($parents as $parent) {
        if ($parent->get('field_usc_type')->value === 'district') {
          return $parent->id();
        }
      }

    }

    return NULL;
  }

  /**
   * Reacts to the items indexed event.
   *
   * @param \Drupal\search_api\Event\IndexingItemsEvent $event
   *   The query alter event.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function indexAlter(IndexingItemsEvent $event) {
    $index = $event->getIndex();
    $items = $event->getItems();

    if ($index->getOriginalId() === 'usc_court_finder_locations') {
      /** @var \Drupal\search_api\Item\Item $item */
      foreach ($items as $item) {
        /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entityAdapter */
        $entityAdapter = $item->getOriginalObject();
        if ($entityAdapter) {
          /** @var \Drupal\usc_court_finder\Entity\Location $location */
          $location = $entityAdapter->getEntity();
          $court_type = $location->get('court_type')->value;
          // Programatically calculate a court type weight, otherwise it is set
          // to zero value.
          $location->get('court_type_weight')->value;
          if ($court_type === 'Appeals Court') {
            $circuit_id = $location->get('circuit_id')->target_id;
            if ($circuit_id) {
              $districts = $this->districtsByCircuitId($circuit_id);
              if ($districts) {
                $location->set('district_id', $districts);
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events = [];

    $events[KernelEvents::REQUEST] = ['onRequest', 100];

    // Workaround to avoid a fatal error during site install from existing
    // config.
    // @see https://www.drupal.org/project/facets/issues/3199156
    if (class_exists('\Drupal\search_api\Event\SearchApiEvents', TRUE)) {
      $events[SearchApiEvents::INDEXING_ITEMS] = 'indexAlter';
    }

    return $events;
  }

}
