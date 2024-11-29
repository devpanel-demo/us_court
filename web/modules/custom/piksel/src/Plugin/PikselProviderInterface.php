<?php

namespace Drupal\piksel\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\piksel\Piksel;

/**
 * Defines the interface for piksel media provider plugins.
 */
interface PikselProviderInterface extends PluginInspectionInterface {

  /**
   * Loads piksel media by ID.
   *
   * @param string $id
   *   The ID of the photo to load.
   *
   * @return \Drupal\piksel\Piksel
   *   The piksel media value object.
   */
  public function load(string $id): Piksel;

  /**
   * Search for piksel projects items by account.
   *
   * @return array
   *   An array of piksel media value objects.
   */
  public function searchProjects(): array;

  /**
   * Search for piksel program items by project.
   *
   * @param string $projectId
   *   The Project ID to search for.
   *
   * @return array
   *   An array of piksel media value objects.
   */
  public function searchPrograms(string $projectId): array;

}
