<?php

namespace Drupal\piksel;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\piksel\Plugin\PikselProviderManager;

/**
 * Provides search results for the piksel media add form.
 */
class PikselSearch {

  use StringTranslationTrait;

  /**
   * The piksel media provider manager.
   *
   * @var \Drupal\piksel\Plugin\PikselProviderManager
   */
  protected $pikselProviderManager;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The piksel media cache wrapper.
   *
   * @var \Drupal\piksel\PikselCacheWrapperInterface
   */
  protected $cacheWrapper;

  /**
   * PikselSearch constructor.
   *
   * @param \Drupal\piksel\Plugin\PikselProviderManager $piksel_provider_manager
   *   The piksel media provider manager.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager.
   * @param \Drupal\piksel\PikselCacheWrapperInterface $cache_wrapper
   *   The piksel media cache wrapper.
   */
  public function __construct(PikselProviderManager $piksel_provider_manager, PagerManagerInterface $pager_manager, PikselCacheWrapperInterface $cache_wrapper) {
    $this->pikselProviderManager = $piksel_provider_manager;
    $this->pagerManager = $pager_manager;
    $this->cacheWrapper = $cache_wrapper;
  }

  /**
   * Search for piksel projects.
   *
   * @param string $provider_name
   *   The piksel media provider name.
   *
   * @return array
   *   An array of piksel media results.
   *
   * @throws \Exception
   */
  public function searchProjects(string $provider_name): array {
    $provider = $this->pikselProviderManager->createInstance($provider_name);
    $results  = $this->cacheWrapper->searchProjects($provider);
    return $results;
  }

  /**
   * Search for piksel programs.
   *
   * @param string $provider_name
   *   The piksel media provider name.
   * @param string $project_id
   *   The piksel project id.
   *
   * @return array
   *   An array of piksel programs results.
   *
   * @throws \Exception
   */
  public function searchPrograms(string $provider_name, string $project_id): array {
    $provider = $this->pikselProviderManager->createInstance($provider_name);
    $results  = $this->cacheWrapper->searchPrograms($provider, $project_id);
    return $results;
  }

}
