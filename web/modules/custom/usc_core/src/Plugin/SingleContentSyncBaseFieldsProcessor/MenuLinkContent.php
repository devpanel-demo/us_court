<?php

namespace Drupal\usc_core\Plugin\SingleContentSyncBaseFieldsProcessor;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\single_content_sync\ContentExporterInterface;
use Drupal\single_content_sync\ContentImporterInterface;
use Drupal\single_content_sync\SingleContentSyncBaseFieldsProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for node base fields processor plugin.
 *
 * @SingleContentSyncBaseFieldsProcessor(
 *   id = "menu_link_content",
 *   label = @Translation("Menu link content processor"),
 *   entity_type = "menu_link_content",
 * )
 */
class MenuLinkContent extends SingleContentSyncBaseFieldsProcessorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The content exporter.
   *
   * @var \Drupal\single_content_sync\ContentExporterInterface
   */
  protected ContentExporterInterface $exporter;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The content importer.
   *
   * @var \Drupal\single_content_sync\ContentImporterInterface
   */
  protected ContentImporterInterface $importer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContentExporterInterface $exporter, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, ContentImporterInterface $importer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->exporter = $exporter;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->importer = $importer;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('single_content_sync.exporter'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('single_content_sync.importer')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function exportBaseValues(FieldableEntityInterface $entity): array {
    assert($entity instanceof MenuLinkContentInterface);

    $base_fields = [
      'title' => $entity->getTitle(),
      'enabled' => $entity->isPublished(),
      'expanded' => $entity->isExpanded(),
      'langcode' => $entity->language()->getId(),
      'menu_name' => $entity->get('menu_name')->value,
      'description' => $entity->getDescription(),
      'link' => $entity->get('link')->getValue(),
      'weight' => $entity->getWeight(),
      'parent' => '',
    ];

    // Export parent menu link.
    if ($entity->getParentId()) {
      [, $parent_uuid] = explode(':', $entity->getParentId());
      $parent = $this->entityRepository->loadEntityByUuid($entity->getEntityTypeId(), $parent_uuid);

      if ($parent instanceof MenuLinkContentInterface) {
        $base_fields['parent'] = $this->exporter->doExportToArray($parent);
      }
    }

    return $base_fields;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function mapBaseFieldsValues(array $values, FieldableEntityInterface $entity): array {
    $baseFields = [
      'title' => $values['title'],
      'enabled' => $values['enabled'],
      'expanded' => $values['expanded'],
      'langcode' => $values['langcode'],
      'menu_name' => $values['menu_name'],
      'description' => $values['description'],
      'weight' => $values['weight'],
      'link' => $values['link'],
      'parent' => '',
    ];

    // Import parent menu link first.
    if (!empty($values['parent'])) {
      $parent = $this->importer->doImport($values['parent']);
      $baseFields['parent'] = implode(':', ['menu_link_content', $parent->uuid()]);
    }

    // Import linked entity.
    foreach ($baseFields['link'] as &$item) {
      if (!isset($item['entity'])) {
        continue;
      }

      // If the entity was fully exported we do the full import.
      if ($this->importer->isFullEntity($item['entity'])) {
        $this->importer->doImport($item['entity']);
      }

      $linked_entity = $this->entityRepository->loadEntityByUuid($item['entity']['entity_type'], $item['entity']['uuid']);

      if (!$linked_entity) {
        $linked_entity = $this->importer->createStubEntity($item['entity']);
      }

      $item['uri'] = "entity:{$linked_entity->getEntityTypeId()}/{$linked_entity->id()}";
    }

    return $baseFields;
  }

}
