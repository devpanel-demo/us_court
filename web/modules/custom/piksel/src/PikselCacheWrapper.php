<?php

namespace Drupal\piksel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\piksel\Plugin\PikselProviderInterface;

/**
 * Cache wrapper for piksel media API calls.
 */
class PikselCacheWrapper implements PikselCacheWrapperInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs Cache provider Service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(CacheBackendInterface $cache, TimeInterface $time) {
    $this->cache = $cache;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function searchProjects(PikselProviderInterface $provider): array {
    $cid = 'searchProjects:' . $provider->getPluginId();

    // Try to fetch the data from the cache.
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    // If cached data is not available, fetch the data from the provider.
    $result = $provider->searchProjects();
    $this->cache->set($cid, $result, $this->generateExpireTimestamp($provider, 'searchProjects'));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function searchPrograms(PikselProviderInterface $provider, string $projectId): array {
    $cid = 'searchPrograms:' . $provider->getPluginId() . ':' . Crypt::hashBase64($projectId);

    // Try to fetch the data from the cache.
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    // If cached data is not available, fetch the data from the provider.
    $result = $provider->searchPrograms($projectId);
    $this->cache->set($cid, $result, $this->generateExpireTimestamp($provider, 'searchPrograms'));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function load(PikselProviderInterface $provider, string $id): Piksel {
    $cid = 'load:' . $provider->getPluginId() . ':' . $id;

    // Try to fetch the data from the cache.
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    // If cached data is not available, fetch the data from the provider.
    $result = $provider->load($id);
    $this->cache->set($cid, $result, $this->generateExpireTimestamp($provider, 'load'));

    return $result;
  }

  /**
   * Generate an expire timestamp.
   *
   * The expire timestamp can be set in the settings.php file. The default for
   * all API calls is 24 hours. The expire timestamp can be set per API call
   * type (search or load) and provider. For example:
   * $settings['piksel.pexels.cache.search'] = 60 * 60;
   * $settings['piksel.pexels.cache.load'] = 60 * 60 * 24;
   *
   * @param \Drupal\piksel\Plugin\PikselProviderInterface $provider
   *   The PikselProvider class.
   * @param string $type
   *   The type of API call.
   *
   * @return int
   *   Unix timestamp when to expire this item.
   */
  protected function generateExpireTimestamp(PikselProviderInterface $provider, string $type): int {
    $settings_key = 'piksel.' . $provider->getPluginId() . '.cache.' . $type;
    return $this->time->getRequestTime() + Settings::get($settings_key, 60 * 60 * 24);
  }

}
