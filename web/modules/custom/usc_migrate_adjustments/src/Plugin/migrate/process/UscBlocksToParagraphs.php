<?php

namespace Drupal\usc_migrate_adjustments\Plugin\migrate\process;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * USC Blocks process plugin.
 *
 * Maps D7 blocks with D10 parapraphs using configured migrations.
 *
 * @MigrateProcessPlugin(
 *   id = "usc_blocks_to_paragraphs",
 *   handle_multiples = TRUE
 * )
 */
class UscBlocksToParagraphs extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The block_content entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $blockContentStorage;

  /**
   * The second database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $upgradeDatabase;

  /**
   * Constructs a UscBlockS object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface|null $storage
   *   The block content storage object. NULL if the block_content module is
   *   not installed.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\Core\Database\Connection $upgrade_database
   *   The second database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ?EntityStorageInterface $storage, MigrateLookupInterface $migrate_lookup, Connection $upgrade_database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockContentStorage = $storage;
    $this->migrateLookup = $migrate_lookup;
    $this->upgradeDatabase = $upgrade_database;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager->hasDefinition('block_content') ? $entity_type_manager->getStorage('block_content') : NULL,
      $container->get('migrate.lookup'),
      $container->get('usc_migrate_adjustments.upgrade_database'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $migrations = $this->configuration['migration'];

    if (empty($migrations)) {
      return NULL;
    }
    if (is_array($value)) {
      $results = [];
      foreach ($value as $id) {
        if (empty($id['target_id'])) {
          continue;
        }

        $bid = $id['target_id'];

        $revision_id = $this->getRevisionId($bid);

        if (empty($revision_id)) {
          continue;
        }

        $lookup_result = $this->migrateLookup->lookup($migrations, [$bid, $revision_id]);

        if ($lookup_result) {
          foreach ($lookup_result as $block_ids) {
            $results[] = [
              'target_id' => $block_ids['id'],
              'target_revision_id' => $block_ids['revision_id'],
            ];
          }
        }
      }

      return $results;
    }
    else {
      return NULL;
    }
  }

  /**
   * Provide a revision id by a block id.
   *
   * @param int $bid
   *   A block id.
   *
   * @return int|false
   *   A revision id if a block exists, otherwise FALSE.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  private function getRevisionId(int $bid): int|FALSE {
    $query = $this->upgradeDatabase->select('bean', 'b');
    $query->addField('b', 'vid');
    $query->condition('b.bid', $bid, '=');
    $revision_id = $query->execute()->fetchField();

    return $revision_id;
  }

}
