<?php

namespace Drupal\usc_migrate_adjustments\Plugin\migrate\process;

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
 * Maps D7 blocks with migrated D10 using only configured migrations.
 *
 * @MigrateProcessPlugin(
 *   id = "usc_blocks",
 *   handle_multiples = TRUE
 * )
 */
class UscBlockS extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ?EntityStorageInterface $storage, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockContentStorage = $storage;
    $this->migrateLookup = $migrate_lookup;
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
      $container->get('migrate.lookup')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
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
        $lookup_result = $this->migrateLookup->lookup($migrations, [$id['target_id']]);

        if ($lookup_result) {
          // Return only the latest revision of the block.
          $candidate_value = [];
          foreach ($lookup_result as $block_ids) {
            if (empty($candidate_value) || $candidate_value['revision_id'] < $block_ids['revision_id']) {
              $candidate_value = $block_ids;
            }
          }
          if (!empty($candidate_value)) {
            $results[] = $candidate_value['id'];
          }
        }
      }

      return $results;
    }
    else {
      return NULL;
    }
  }

}
