<?php

namespace Drupal\usc_migrate_boolean\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Boolean migration event subscriber.
 */
class BooleanMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a new BooleanMigrationSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MigrationPluginManagerInterface $migration_plugin_manager, MigrateLookupInterface $migrate_lookup) {
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * Migrate prepare row event handler.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $this->listToBoolean($event);
  }

  /**
   * Migrates list fields to boolean.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  private function listToBoolean(MigratePrepareRowEvent $event) {
    $row = $event->getRow();
    $source = $event->getSource();

    if (in_array($source->getPluginId(), [
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_per_form_display',
    ])) {

      $field_name = $row->getSourceProperty('field_name');

      $boolean_fields = _usc_migrate_boolean_get_boolean_fields();

      if (array_key_exists($field_name, $boolean_fields)) {

        // Update field type for field and field storage migrations.
        if (in_array($source->getPluginId(), [
          'd7_field',
          'd7_field_instance',
        ])) {
          $row->setSourceProperty('type', 'boolean');
          $row->setDestinationProperty('type', 'boolean');
        }
        // Update form widget to show booleans as radios.
        elseif (in_array($source->getPluginId(), [
          'd7_field_instance_per_form_display',
        ])) {

          $row->setSourceProperty('type', 'options_buttons');

          $options = $row->getSourceProperty('options');

          $options['type'] = 'options_buttons';
          $options['settings'] = [];
          $row->setDestinationProperty('options', $options);
        }

        // Set labels for on/off.
        if ($source->getPluginId() == 'd7_field_instance') {

          $allowed_values = $boolean_fields[$field_name]['allowed_values'];
          $settings = [];
          foreach ($allowed_values as $key => $value) {
            $settings[$value['d10_key']] = $value['label'];
          }
          $row->setSourceProperty('settings', $settings);
        }

      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigratePlusEvents::PREPARE_ROW => ['onPrepareRow'],
    ];
  }

}
