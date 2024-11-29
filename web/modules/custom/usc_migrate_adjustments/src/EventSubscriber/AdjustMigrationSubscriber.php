<?php

namespace Drupal\usc_migrate_adjustments\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Uuid\UuidInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adjust migrations event subscriber.
 */
class AdjustMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The second database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $upgradeDatabase;

  /**
   * Constructs a new AdjustMigrationSubscriber instance.
   *
   * @param \Drupal\Core\Database\Connection $upgrade_database
   *   The second database connection.
   */
  public function __construct(Connection $upgrade_database) {
    $this->upgradeDatabase = $upgrade_database;
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
    $this->adjustMigrationRows($event);
  }

  /**
   * Performs migration adjustments to fix the different issues.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  private function adjustMigrationRows(MigratePrepareRowEvent $event) {
    $row = $event->getRow();
    $source = $event->getSource();
    $migrationId = $event->getMigration()->getBaseId();
    $pluginId = $source->getPluginId();

    // Transform entity reference fields pointing to bean entities so
    // they point to block_content ones.
    if (($pluginId == 'd7_field') && ($row->getSourceProperty('type') == 'entityreference')) {
      $settings = $row->getSourceProperty('settings');
      if ($settings['target_type'] == 'bean') {
        $settings['target_type'] = 'block_content';
        $row->setSourceProperty('settings', $settings);
        $row->setDestinationProperty('settings', $settings);
      }
    }

    $plugins = ['d7_field', 'd7_field_instance'];

    // Instances which cause MigrateSkipRowException exection.
    // see \Drupal\text\Plugin\migrate\field\d7\TextField::getFieldType()
    // line 97 throws new MigrateSkipRowException.
    if ((in_array($pluginId, $plugins)) && ($row->getSourceProperty('field_name') == 'field_fc_be_body')) {
      $instances = $row->getSourceProperty('instances');
      $updated = FALSE;
      foreach ($instances as &$instance) {

        $data = unserialize($instance['data'], ['allowed_classes' => FALSE]);
        if ($data['settings']['text_processing'] == '0') {
          $data['settings']['text_processing'] = '1';
          $instance['data'] = serialize($data);
          $updated = TRUE;
        }
      }

      if ($updated) {
        $row->setSourceProperty('instances', $instances);
      }
    }

    // Fix a form widget for tags.
    if (($pluginId == 'd7_field_instance_widget_settings') && ($row->getSourceProperty('field_name') == 'field_tags')) {
      $row->setSourceProperty('type', 'entity_reference_autocomplete_tags');
    }

    // Bean entity saves data in the serialize format, we need to
    // unserialize to process properly.
    $migrations = [
      'sidebar_gov_delivery',
      'sidebar_notifications',
      'sidebar_related_news',
      'embedded_widget_highcharts',
      'embedded_widget_map',
      'entity_video_youtube_playlist'
    ];
    if (in_array($migrationId, $migrations)) {
      $data = $row->getSourceProperty('data');
      if (!empty($data) && is_string($data)) {
        $data = unserialize($data, ['allowed_classes' => FALSE]);
        // Handle an edge case of a YouTube playlist.
        if ($migrationId === 'entity_video_youtube_playlist') {
          if (!empty($data['url']) && $data['url'] == 'http://www.youtube.com/embed/videoseries?list=PL4bcxoLSIaXfnPbstDa4HxZDN9snB4WlO') {
            $data['url'] = 'https://www.youtube.com/playlist?list=PL4bcxoLSIaXfnPbstDa4HxZDN9snB4WlO';
          }
        }
        if (is_array($data)) {
          $row->setSourceProperty('data', $data);
        }
      }
    }

    // Replace field names with human-friendly labels.
    if ($migrationId === 'upgrade_d7_node_complete_data_table') {
      $field_reporting_period_increment = $row->getSourceProperty('field_reporting_period_increment');
      if ($field_reporting_period_increment && !empty($field_reporting_period_increment[0]['value'])) {
        $labels = [
          '1m' => '1 Month',
          '12m' => '12 Months',
          '2y' => '2 Years',
          '3m' => '3 Months',
          '5y' => '5 Years',
          '6y' => '6 Years or more',
          'p' => 'Pending',
        ];
        $key = $field_reporting_period_increment[0]['value'];
        if (array_key_exists($key, $labels)) {
          $field_reporting_period_increment[0]['value'] = $labels[$key];
          $row->setSourceProperty('field_reporting_period_increment', $field_reporting_period_increment);
        }
      }
    }

    // Replace piksel alt values.
    if ($migrationId === 'upgrade_d7_field_collection_piksel_programs') {
      $field_program = $row->getSourceProperty('field_piksel_program');
      if (!empty($field_program)) {
        $row->setSourceProperty('field_alternate_text', current($field_program)['alt']);
        $row->setSourceProperty('name', current($field_program)['title']);
      }
    }

    // Add the missing wb properties for the proper migration of the moderation
    // state.
    $node_migrations = [
      'upgrade_d7_node_complete_basic_simple',
      'upgrade_d7_node_complete_basic',
      'upgrade_d7_node_complete_camera',
      'upgrade_d7_node_complete_data_table',
      'upgrade_d7_node_complete_educational_activity',
      'upgrade_d7_node_complete_faqs',
      'upgrade_d7_node_complete_fed_probation_journal',
      'upgrade_d7_node_complete_federal_rules',
      'upgrade_d7_node_complete_fee',
      'upgrade_d7_node_complete_form',
      'upgrade_d7_node_complete_judge',
      'upgrade_d7_node_complete_judge_comments',
      'upgrade_d7_node_complete_landing_featured_content',
      'upgrade_d7_node_complete_landing_view',
      'upgrade_d7_node_complete_news',
      'upgrade_d7_node_complete_news_issue',
      'upgrade_d7_node_complete_report',
    ];
    if (in_array($migrationId, $node_migrations)) {

      $nid = $row->getSourceProperty('nid');
      $vid = $row->getSourceProperty('vid');
      // Generate a stable UUID based on the nid.
      $uuid = $this->generateDeterministicUuid($nid);
      // Set the UUID on the row object.
      $row->setDestinationProperty('uuid', $uuid);

      $status = (bool) $row->getSourceProperty('status');
      $row->setSourceProperty('language', 'en');
      $row->setSourceProperty('revision_translation_affected', 1);
      // The failback logic to set wb_* properties when the revision does not
      // have transitions and wbm2cm_migrate_prepare_row() will skip it. In this
      // case the revision will be migrated as a draft even it can be published.
      if ($nid && $vid && !$this->hasTransition($nid, $vid)) {
        $row->setSourceProperty('wb_published', $status);
        $row->setSourceProperty('wb_is_current', $this->isCurrentRevision($nid, $vid));
        $state = $status ? 'published' : 'draft';
        $row->setSourceProperty('wb_state', $state);
      }

      $access_id = $this->getWorkbenchAccessId($nid);
      if ($access_id) {
        $row->setSourceProperty('workbench_access', $access_id);
      }
    }

    if ($migrationId == 'upgrade_d7_menu_links') {

      $menu_parent = $row->getSourceProperty('parent');
      $plid = $row->getSourceProperty('plid');
      $id = $row->getSourceProperty('mlid');
      $parents_mapping = [
        '4063' => 742,
        '4045' => 760,
        '3862' => 8277,
        '4036' => 811,
        '4053' => 766,
        '3982' => 584,
        '3978' => 582,
        '4047' => 716,
        '5081' => 808,
        '6241' => 670,
        '2948' => 693,
        '5818' => 942,
      ];
      // Variable is getting clean up on every migration import.
      if ($row->getSourceProperty('menu_name') == 'main-menu') {
        $row->setSourceProperty('menu_name', 'main');
        if (!empty($parents_mapping[$plid])) {
          $row->setSourceProperty('plid', $parents_mapping[$plid]);
          $_SESSION['parents'][$id] = $id;
        }
        else {
          if (!empty($_SESSION['parents'][$plid])) {
            $_SESSION['parents'][$id] = $id;
          }
          else {
            throw new MigrateSkipRowException();
          }
        }
      }
      else {
        throw new MigrateSkipRowException();
      }
    }
  }

  /**
   * Generate a stable UUID from nid.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return string
   *   The generated UUID.
   */
  private function generateDeterministicUuid($nid) {
    // Create a namespace to ensure uniqueness (e.g., based on site domain).
    $namespace = 'usc_migrate_adjustments';

    // Hash the nid and namespace to create a consistent, deterministic UUID.
    $hash = md5($namespace . $nid);

    // Convert the hash to a UUID format (version 4-like UUID structure).
    return sprintf(
      '%08s-%04s-%04x-%04x-%12s',
      substr($hash, 0, 8),
      substr($hash, 8, 4),
      (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x4000,
      (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
      substr($hash, 20, 12)
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigratePlusEvents::PREPARE_ROW => ['onPrepareRow'],
    ];
  }

  /**
   * Check if the revision is current.
   *
   * @param int $nid
   *   Node id.
   * @param int $vid
   *   Revision id.
   *
   * @return bool
   *   True if a revision is current otherwise false.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  private function isCurrentRevision(int $nid, int $vid) : bool {
    $query = $this->upgradeDatabase->select('node', 'n');
    $query->addField('n', 'vid');
    $query->condition('n.nid', $nid, '=');
    $query->condition('n.vid', $vid, '=');
    $revision_id = $query->execute()->fetchField();
    return (bool) $revision_id;
  }

  /**
   * Check if the revision has a wb transition.
   *
   * @param int $nid
   *   Node id.
   * @param int $vid
   *   Revision id.
   *
   * @return bool
   *   True if a revision has a wb transition otherwise false.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  private function hasTransition(int $nid, int $vid): bool {
    $query = $this->upgradeDatabase->select('workbench_moderation_node_history', 'wmnh');
    $query->addField('wmnh', 'state');
    $query->condition('wmnh.nid', $nid, '=');
    $query->condition('wmnh.vid', $vid, '=');
    $query->range(0, 1);
    $query->orderBy('wmnh.stamp', 'desc');
    $state = $query->execute()->fetchField();
    return (bool) $state;
  }

  /**
   * Get the access id for the node.
   *
   * @param int $nid
   *   Node id.
   *
   * @return mixed
   *   The access id if exists.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  private function getWorkbenchAccessId(int $nid) {
    $query = $this->upgradeDatabase->select('workbench_access_node', 'wan');
    $query->addField('wan', 'access_id');
    $query->condition('wan.nid', $nid, '=');
    $query->range(0, 1);
    $access_id = $query->execute()->fetchField();
    return $access_id;
  }

}
