<?php

namespace Drupal\piksel;

use Drupal\piksel\Plugin\PikselProviderInterface;

/**
 * Defines the interface for the Cache Provider.
 */
interface PikselCacheWrapperInterface {

  /**
   * Search for piksel program items by project.
   *
   * @param \Drupal\piksel\Plugin\PikselProviderInterface $provider
   *   The PikselProvider plugin.
   * @param string $projectId
   *   The Project ID to search for.
   *
   * @return array
   *   An array of piksel media value objects.
   */
  public function searchPrograms(PikselProviderInterface $provider, string $projectId): array;

  /**
   * Search for piksel projects items by account.
   *
   * @param \Drupal\piksel\Plugin\PikselProviderInterface $provider
   *   The PikselProvider plugin.
   *
   * @return array
   *   An array of piksel media value objects.
   */
  public function searchProjects(PikselProviderInterface $provider): array;

  /**
   * Loads piksel media by ID.
   *
   * @param \Drupal\piksel\Plugin\PikselProviderInterface $provider
   *   The PikselProvider plugin class.
   * @param string $id
   *   The ID of the photo to load.
   *
   * @return \Drupal\piksel\Piksel
   *   The piksel media value object.
   */
  public function load(PikselProviderInterface $provider, string $id): Piksel;

}
