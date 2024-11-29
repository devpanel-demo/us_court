<?php

namespace Drupal\usc_migrate_adjustments\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Drush commands.
 */
final class USCMigrateCommands extends DrushCommands {

  /**
   * Constructs a CoreCommands object.
   */
  public function __construct(
    private Connection $database,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Update all nodes with und langcode.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  #[CLI\Command(name: 'usc_migrate:langcode-adjustments', aliases: ['usc_langcode'])]
  #[CLI\Usage(name: 'usc_migrate:langcode-adjustments', description: 'The command updates all nodes langcode.')]
  public function updateLangcode() {
    // Query for all node IDs.
    $query = $this->database->select('node_revision', 'nr')
      ->distinct()
      ->fields('nr', ['nid'])
      ->condition('nr.langcode', 'und');

    $nids = $query->execute()->fetchCol();

    // Create the batch operation.
    $batch = [
      'title' => t('Updating node langcode...'),
      'operations' => [],
      'finished' => [self::class, 'migrateUpdateNodeLangcodeFinished'],
    ];

    // Split node IDs into smaller chunks for batch processing.
    $chunks = array_chunk($nids, 50);

    foreach ($chunks as $chunk) {
      // Add each chunk as a separate batch operation.
      $batch['operations'][] = [[self::class, 'migrateProcessBatch'], [$chunk]];
    }

    batch_set($batch);
    drush_backend_batch_process();

    $this->logger->notice(dt('Processed all media entities.'));
  }

  /**
   * Batch processing callback to update nodes.
   *
   * @param array $nids
   *   The node IDs to process in this batch.
   * @param array &$context
   *   The batch context.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  public static function migrateProcessBatch(array $nids, array &$context) {

    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = [];
    }

    // Get the database connection.
    $connection = Database::getConnection();

    foreach ($nids as $nid) {
      // Load the current node to update its langcode.
      $connection->update('node')
        ->fields(['langcode' => 'en'])
        ->condition('nid', $nid)
        ->execute();

      // Query all revisions of the node and update the langcode.
      $query = $connection->select('node_revision', 'nr')
        ->fields('nr', ['vid'])
        ->condition('nr.nid', $nid)
        ->condition('nr.langcode', 'und');
      $revisions = $query->execute()->fetchAll();

      foreach ($revisions as $revision) {
        $rids[] = $revision->vid;
      }

      if (!empty($rids)) {
        $connection->update('node_revision')
          ->fields(['langcode' => 'en', 'revision_default' => 0])
          ->condition('vid', $rids, 'IN')
          ->execute();
        $connection->update('node_field_revision')
          ->fields(['langcode' => 'en'])
          ->condition('nid', $nid)
          ->execute();
      }
      $context['results']['updated'][] = $nid;
    }

    // Provide feedback during batch process.
    $context['message'] = t('Processing nodes: @count updated so far.', ['@count' => count($context['results']['updated'])]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Indicates whether the batch completed successfully.
   * @param array $results
   *   The results of the batch operations.
   * @param array $operations
   *   Any remaining operations.
   */
  public static function migrateUpdateNodeLangcodeFinished($success, array $results, array $operations) {
    if ($success && !empty($results['updated'])) {
      $message = t('Successfully updated langcode for @count nodes.', ['@count' => count($results['updated'])]);
    }
    else {
      if (empty($results['updated'])) {
        $message = t('There are no nodes with UND langcode');
      }
      else {
        $message = t('The batch process encountered an error.');
      }
    }
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Remove all nodes after certain id and set new incremental values.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  #[CLI\Command(name: 'usc_migrate:node-cleanup', aliases: ['usc_node_cleanup'])]
  #[CLI\Usage(name: 'usc_migrate:node-cleanup', description: 'The command removes all nodes created after the migration.')]
  public function nodesCleanUp() {
    // Query for all node IDs.
    $query = $this->database->select('node_revision', 'nr')
      ->distinct()
      ->fields('nr', ['nid'])
      ->condition('nr.nid', 50000, '>=');

    $nids = $query->execute()->fetchCol();

    // Create the batch operation.
    $batch = [
      'title' => t('Updating node langcode...'),
      'operations' => [],
      'finished' => [self::class, 'migrateCleanupNodesFinished'],
    ];

    // Split node IDs into smaller chunks for batch processing.
    $chunks = array_chunk($nids, 50);

    foreach ($chunks as $chunk) {
      // Add each chunk as a separate batch operation.
      $batch['operations'][] = [[self::class, 'migrateCleanupBatch'], [$chunk]];
    }

    batch_set($batch);
    drush_backend_batch_process();

    $this->logger->notice(dt('Processed all media entities.'));
  }

  /**
   * Batch processing callback to update nodes.
   *
   * @param array $nids
   *   The node IDs to process in this batch.
   * @param array &$context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function migrateCleanupBatch(array $nids, array &$context) {

    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = [];
    }

    // Get the database connection.
    $connection = Database::getConnection();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach ($nids as $nid) {
      $node = $node_storage->load($nid);
      if ($node) {
        $node->delete();
      }
      $context['results']['updated'][] = $nid;
    }

    // Provide feedback during batch process.
    $context['message'] = t('Processing nodes: @count updated so far.', ['@count' => count($context['results']['updated'])]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Indicates whether the batch completed successfully.
   * @param array $results
   *   The results of the batch operations.
   * @param array $operations
   *   Any remaining operations.
   */
  public static function migrateCleanupNodesFinished($success, array $results, array $operations) {
    if ($success && !empty($results['updated'])) {
      $message = t('Successfully removed @count nodes.', ['@count' => count($results['updated'])]);
    }
    else {
      if (empty($results['updated'])) {
        $message = t('There are no nodes to remove');
      }
      else {
        $message = t('The batch process encountered an error.');
      }
    }
    \Drupal::messenger()->addMessage($message);
  }

}
