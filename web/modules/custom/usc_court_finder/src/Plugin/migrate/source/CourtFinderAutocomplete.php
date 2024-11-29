<?php

namespace Drupal\usc_court_finder\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * USC Court Finder Autocomplete Entity Item source plugin.
 *
 * @MigrateSource(
 *   id = "usc_court_finder_item"
 * )
 */
class CourtFinderAutocomplete extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CourtFinderAutocomplete object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Prints the query string when the object is used as a string.
   */
  public function __toString() {
    return 'The court finder item data';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['term']['type'] = 'string';
    $ids['type']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'term' => $this->t('The court finder item term'),
      'type' => $this->t('The court finder item type'),
      'weight' => $this->t('The court finder item weight'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  protected function initializeIterator() {
    return new \ArrayIterator($this->getData());
  }

  /**
   * Returns an array of all court finder item values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  protected function getData(): array {

    $results = [];

    // Get the taxonomy term storage.
    $taxonomyTermStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Define the field name.
    $field_name = 'field_usc_type';

    // Get the list of terms with field_usc_type = 'district'.
    $query = $taxonomyTermStorage->getQuery()
      ->condition('vid', 'usc_court_finder_hierarchy')
      ->condition($field_name . '.value', 'district')
      ->accessCheck(FALSE);

    $term_ids = $query->execute();

    // Load the terms.
    $terms = $taxonomyTermStorage->loadMultiple($term_ids);

    // Process the loaded terms as needed.
    foreach ($terms as $term) {
      $results[] = [
        'term' => $term->label(),
        'type' => 'All courts within district',
        'weight' => 10,
      ];
    }

    // Appeals.
    $results = array_merge($results, $this->generateByCourtType('Appeals Court', 'U.S. Courts of Appeals', 20));

    // District.
    $results = array_merge($results, $this->generateByCourtType('District Court', 'U.S. District Courts', 30));

    // Bankruptcy.
    $results = array_merge($results, $this->generateByCourtType('Bankruptcy Court', 'U.S. Bankruptcy Courts', 40));

    // Probation/Pretrial Services.
    $results = array_merge($results, $this->generateByCourtType('Probation/Pretrial Services', 'U.S. Probation and Pretrial Services', 50));

    // Defender.
    $results = array_merge($results, $this->generateByCourtType('Federal Defenders', 'Federal Defender Organizations', 60));

    // Buildings.
    $results = array_merge($results, $this->generateByBuilding());

    // Special jurisdiction.
    $results = array_merge($results, $this->generateByCourtType('Court of Special Jurisdiction', 'Courts of Special Jurisdiction', 80));

    // Other.
    $results = array_merge($results, $this->generateByCourtType('Other Judiciary Agency', 'Other Judiciary Agencies', 90));

    return $results;
  }

  /**
   * Generate court finder item values for court buildings.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  protected function generateByBuilding() {

    $query = $this->database->select('usc_location', 'ul');
    $query->addField('ul', 'building_name');
    $query->condition('ul.building_name', NULL, 'IS NOT NULL');
    $query->distinct();

    $results = [];

    foreach ($query->execute()->fetchAll() as $item) {
      $results[] = [
        'term' => $item->building_name,
        'type' => 'Courthouse Buildings',
        'weight' => 70,
      ];
    }

    return $results;
  }

  /**
   * Generate court finder item values based on court type field.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  protected function generateByCourtType($type, $label, $weight) {

    $query = $this->database->select('usc_location', 'ul');
    $query->addField('ul', 'office_name');
    $query->condition('ul.court_type', $type, '=');
    $query->distinct();

    $results = [];

    foreach ($query->execute()->fetchAll() as $item) {
      $results[] = [
        'term' => $item->office_name,
        'type' => $label,
        'weight' => $weight,
      ];
    }

    return $results;
  }

}
