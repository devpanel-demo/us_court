<?php

namespace Drupal\usc_content_sync;

/**
 * Service for processing content from single content sync.
 */
interface EntitySyncServiceInterface {

  /**
   * Read current directory.
   *
   * @return array
   *   List of files.
   */
  public function getRawFiles();

}
