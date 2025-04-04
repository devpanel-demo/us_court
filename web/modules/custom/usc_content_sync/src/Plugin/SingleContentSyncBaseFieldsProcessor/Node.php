<?php

namespace Drupal\usc_content_sync\Plugin\SingleContentSyncBaseFieldsProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\single_content_sync\ContentExporterInterface;
use Drupal\single_content_sync\SingleContentSyncBaseFieldsProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for node base fields processor plugin.
 *
 * @SingleContentSyncBaseFieldsProcessor(
 *   id = "node",
 *   label = @Translation("Node base fields processor"),
 *   entity_type = "node",
 * )
 */
class Node extends SingleContentSyncBaseFieldsProcessorPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The content exporter.
   *
   * @var \Drupal\single_content_sync\ContentExporterInterface
   */
  protected ContentExporterInterface $exporter;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * A new instance of Node base fields processor plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\single_content_sync\ContentExporterInterface $exporter
   *   The content exporter service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ContentExporterInterface $exporter, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->exporter = $exporter;
    $this->currentUser = $current_user;
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
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('single_content_sync.exporter'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exportBaseValues(FieldableEntityInterface $entity): array {
    /** @var \Drupal\node\NodeInterface $entity */
    $owner = $entity->getOwner();

    $base_fields = [
      'title' => $entity->getTitle(),
      'status' => $entity->isPublished(),
      'langcode' => $entity->language()->getId(),
      'created' => $entity->getCreatedTime(),
      'changed' => $entity->getChangedTime(),
      'author' => $owner ? $owner->getEmail() : NULL,
      // phpcs:ignore
      // @phpstan-ignore-next-line
      'url' => $entity->hasField('path') ? $entity->get('path')->alias : NULL,
      'revision_log_message' => $entity->getRevisionLogMessage(),
      'revision_uid' => $entity->getRevisionUserId(),
    ];

    if ($this->moduleHandler->moduleExists('menu_ui')) {
      $menu_link = menu_ui_get_menu_link_defaults($entity);
      $storage = $this->entityTypeManager->getStorage('menu_link_content');

      // Export content menu link item if available.
      if (!empty($menu_link['entity_id']) && ($menu_link_entity = $storage->load($menu_link['entity_id']))) {
        assert($menu_link_entity instanceof MenuLinkContentInterface);

        // Avoid infinitive loop, export menu link only once.
        // phpcs:ignore
        // @phpstan-ignore-next-line
        if (!$this->exporter->isReferenceCached($menu_link_entity)) {
          $base_fields['menu_link'] = $this->exporter->doExportToArray($menu_link_entity);
        }
      }
    }

    return $base_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function mapBaseFieldsValues(array $values, FieldableEntityInterface $entity): array {
    $baseFields = [
      'title' => $values['title'],
      'langcode' => $values['langcode'],
      'created' => $values['created'],
      'changed' => $values['changed'],
      'status' => $values['status'],
    ];

    // We check if node url alias is filled in.
    if (isset($values['url'])) {
      $baseFields['path'] = [
        'alias' => $values['url'],
        'pathauto' => empty($values['url']),
      ];
    }

    // Load node author.
    $account_provided = !empty($values['author']);
    $account = $account_provided
    ? user_load_by_mail($values['author'])
    : NULL;

    if ($account) {
      $baseFields['uid'] = $account->id();
    }

    // Adjust revision if the author is not available.
    if (!$account_provided || !$account) {
      $log_extra = "\n" . $this->t('Original Author: @author', [
        '@author' => $account_provided ? $values['author'] : $this->t('Unknown'),
      ]);

      if (!empty($values['revision_log_message'])) {
        // phpcs:ignore
        // @phpstan-ignore-next-line
        $entity->setRevisionLogMessage($values['revision_log_message'] . $log_extra);
      }

      if ($this->currentUser->isAuthenticated()) {
        $baseFields['uid'] = $this->currentUser->id();
      }
    }

    return $baseFields;
  }

}
