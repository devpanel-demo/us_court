<?php

namespace Drupal\usc_content_sync\Commands;

use Drupal\usc_content_sync\NodeSyncService;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file for USCourts Content Sync.
 */
class UscContentSyncCommands extends DrushCommands {

  /**
   * The node sync service.
   *
   * @var \Drupal\usc_content_sync\NodeSyncService
   */
  protected $nodeSyncService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NodeSyncService $node_sync_service) {
    $this->nodeSyncService = $node_sync_service;
  }

  /**
   * Update and sync folder with node data.
   *
   * @usage drush usc:exportNodes
   *    Export nodes using the directory path from config.
   *
   * @command usc:exportNodes
   * @aliases uscexnode
   *
   * @throws \Exception
   */
  public function exportNodes() {
    $this->output()->writeln("Syncing nodes folder.");
    $files = $this->nodeSyncService->exportSyncFolder();
  }

}
