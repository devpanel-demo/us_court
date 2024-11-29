<?php

namespace Drupal\usc_vacancies\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PreImport.
 *
 * @package Drupal\usc_vacancies\EventSubscriber
 */
class VacancyImportSubscriber implements EventSubscriberInterface {

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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_IMPORT][] = [
      'preImport',
      0,
    ];
    return $events;
  }

  /**
   * Delete previously attached paragraphs to avoid creating orphans.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event to process.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function preImport(MigrateImportEvent $event) {
    /*
     * If we don't remove all Paragraphs attached to the node before importing,
     * we will bloat the database with orphaned paragraphs.
     */
    $archive_id = '';
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $event->getMigration();
    $source_plugin = $migration->getSourcePlugin();
    if (isset($migration->archive_id)) {
      $archive_id = $migration->archive_id;
      $vacancy_type = '';
      switch ($migration->id()) {
        case 'usc_vacancy_judicial_current_feed':
          $vacancy_type = 'judicial_vacancy';
          break;

        case 'usc_vacancy_judicial_future_feed':
          $vacancy_type = 'judicial_future';
          break;

        case 'usc_vacancy_judicial_confirmation_feed':
          $vacancy_type = 'judicial_confirmation';
          break;

        case 'usc_vacancy_judicial_emergency_feed':
          $vacancy_type = 'judicial_emergency';
          break;

        case 'usc_vacancy_judicial_summary_feed':
          $vacancy_type = 'judicial_summary';
          break;

        case 'usc_job_notice_feed':
          $vacancy_type = 'job_vacancy';
          break;

        default:
          break;
      }
      $destination_ids = $this->migrateLookup->lookup($migration->id(), [$archive_id]);
      $destination_nodes = [];
      if (!empty($destination_ids)) {
        foreach ($destination_ids as $key => $destination_id) {
          $destination_nodes[] = $destination_id['nid'];
        }
        $nodes = $this->entityTypeManager
          ->getStorage('node')
          ->loadMultiple($destination_nodes);

        if (!empty($nodes)) {
          foreach ($nodes as $node) {
            $paragraphs = $node->get('field_vacancy_reference')->referencedEntities();
            foreach ($paragraphs as $paragraph) {
              $paragraph->delete();
            }
          }
        }
      }
    }

  }

}
