<?php

namespace Drupal\usc_migrate_list\EventSubscriber;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class USCListMigrationSubscriber, Skip unused fields, populate list properly.
 */
class USCListMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Field names to migrate.
   *
   * @var array
   */
  protected $fieldNames = [
    "field_criminogenic_subject_area",
    "field_reporting_period_increment"
  ];

  /**
   * Constructs a new USCListMigrationSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PREPARE_ROW] = 'onPrepareRow';
    return $events;
  }

  /**
   * This method is called when the onPrepareRow is dispatched.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The dispatched event.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   If a row needs to be skipped.
   * @throws \Exception
   *   If the source cannot be changed.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $this->populateTermsMigration($event);
  }

  /**
   * Skips field instance migrations.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The migrate row event.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   If a row needs to be skipped.
   * @throws \Exception
   *   If the source cannot be changed.
   */
  private function populateTermsMigration(MigratePrepareRowEvent $event) {
    $migrationId = $event->getMigration()->id();
    $row = $event->getRow();
    foreach ($this->fieldNames as $key => $field) {
      if (!empty($row->getSourceProperty($field))) {
        $list = $row->getSourceProperty($field);
        $items = [];
        foreach ($list as $key2 => $item) {
          if (!empty($item["value"])) {
            $items[] = $item["value"];
          }
        }
        $row->setSourceProperty($field, $items);
      }
    }
  }

}
