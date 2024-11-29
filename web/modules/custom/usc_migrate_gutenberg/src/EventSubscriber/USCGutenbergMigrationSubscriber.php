<?php

namespace Drupal\usc_migrate_gutenberg\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\html_processors\Service\HtmlGutenbergParser;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class USCGutenbergMigrationSubscriber.
 */
class USCGutenbergMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The plugin.manager.html_gutenberg_parser service.
   *
   * @var \Drupal\html_processors\Service\HtmlGutenbergParser
   */
  protected $htmlGutenbergParser;

  /**
   * Constructs a new USCGutenbergMigrationSubscriber object.
   */
  public function __construct(HtmlGutenbergParser $html_gutenberg_parser) {
    $this->htmlGutenbergParser = $html_gutenberg_parser;
  }

  /**
   * Field names to migrate.
   *
   * @var array
   */
  protected $fieldNames = [
    "body",
  ];

  /**
   * Migration ids to adjust.
   *
   * @var array
   */
  protected $migrationIds = [
    "upgrade_d7_node_complete_landing_view",
    "upgrade_d7_node_complete_landing_featured_content",
  ];

  /**
   * Gutenberg processors configuration.
   *
   * @var array
   */
  protected $processorConfiguration = [
    "map" => [
      "disabled" => 0,
      "id" => "map",
    ],
    "table_cell" => [
      "disabled" => FALSE,
      "id" => "table_cell",
      "allowed_node_names" => "a #text strong img br",
      "allowed_attributes" => "class",
    ],
    "image" => [
      "remote_url" => "https://uscourts-dev.agileana.com",
      "id" => "image",
      "disabled" => FALSE,
    ],
    "heading" => [
      "id" => "heading",
      "disabled" => FALSE,
    ],
    "ordered_list" => [
      "id" => "ordered_list",
      "disabled" => 0,
    ],
    "unordered_list" => [
      "id" => "unordered_list",
      "disabled" => 0,
    ],
    "list_item" => [
      "id" => "list_item",
      "disabled" => 0,
    ],
    "separator" => [
      "id" => "separator",
      "disabled" => 0,
    ],
    "table" => [
      "id" => "table",
      "disabled" => FALSE,
      "first_row_to_thead" => TRUE,
      "header_attribute" => "",
    ],
    "paragraph" => [
      "id" => "paragraph",
      "disabled" => 0,
    ],
    "container" => [
      "id" => "container",
      "disabled" => 0,
    ]
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PREPARE_ROW] = "onPrepareRow";
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
    $this->migrateFormatterMigrations($event);
  }

  /**
   * Alter field instance migrations.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The migrate row event.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   If a row needs to be skipped.
   * @throws \Exception
   *   If the source cannot be changed.
   */
  private function migrateFormatterMigrations(MigratePrepareRowEvent $event) {
    if (in_array($event->getMigration()->id(), $this->migrationIds)) {
      $row = $event->getRow();
      foreach ($this->fieldNames as $key => $field) {
        $text = current($row->getSourceProperty($field));
        $paragraphs = $row->getSourceProperty('field_referenced_nodes');
        $referencedNodes = [];
        if (!empty($paragraphs)) {
          foreach ($paragraphs as $key => $value) {
            $nodes = $this->getReferencedNodes($value['revision_id']);
            foreach ($nodes as $key2 => $node) {
              $referencedNodes[] = $node['field_fc_rn_node_row_target_id'];
            }
          }
          if ((!empty($text["value"]) && !empty($text["format"])) || !empty($referencedNodes)) {
            $parsedText = '';
            // Process html with Gutenberg processors.
            if (!empty($text["value"]) && !empty($text["format"])) {
              $parsedText = $this->htmlGutenbergParser->parse($text["value"], $this->processorConfiguration);
            }
            if (!empty($referencedNodes)) {
              $type = $row->getSourceProperty('field_landing_page_type');
              if (!empty($type) && !empty($type[0]) && $type[0]['value'] == 1) {
                $view_mode = 'section_landing';
              }
              else {
                $view_mode = 'featured';
              }
              $row->getSourceProperty('field_referenced_nodes');
              $ids = implode(',', $referencedNodes);
              $parsedText .= '<!-- wp:uswdscollection/basic {"nodeIds":[' . $ids . '],"viewMode":"' . $view_mode . '","collectionRange":' . count($referencedNodes) . '} /-->';
            }
            $row->setSourceProperty($field, [
              "value" => $parsedText,
              "format" => "gutenberg",
              "summary" => !empty($text["summary"]) ? $text["summary"] : '',
            ]);
          }
        }
      }
    }
  }

  /**
   * This method is called when tthe row has referenced nodes.
   *
   * @param string $paragraph_id
   *   The paragraph id.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   *   Database connection error.
   */
  public function getReferencedNodes(string $paragraph_id) {
    $connection = Database::getConnection('default', 'migration');
    $options = [];
    $options['fetch'] = \PDO::FETCH_ASSOC;
    $query = $connection->select('field_revision_field_fc_rn_node_row', 'fc', $options)
      ->distinct()
      ->fields('fc')
      ->orderBy('fc.delta')
      ->condition('fc.revision_id', $paragraph_id);
    $fields = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $fields;
  }

}
