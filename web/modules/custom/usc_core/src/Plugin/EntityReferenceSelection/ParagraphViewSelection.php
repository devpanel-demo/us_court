<?php

namespace Drupal\usc_core\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "usc_paragraph",
 *   label = @Translation("Paragraphs - Views: Filter by an entity reference view"),
 *   group = "usc_paragraph",
 *   entity_types = {"paragraph"},
 *   weight = 0
 * )
 */
class ParagraphViewSelection extends ViewsSelection implements SelectionWithAutocreateInterface {


  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  public $entityTypeBundleInfo;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'negate' => 0,
      'target_bundles_drag_drop' => [],
    ];
  }

  /**
   * Constructs a new DefaultSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, RendererInterface $renderer, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $renderer);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $entity_type_id = $this->configuration['target_type'];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    $bundle_options = [];
    $bundle_options_simple = [];

    // Default weight for new items.
    $weight = count($bundles) + 1;

    foreach ($bundles as $bundle_name => $bundle_info) {
      $bundle_options_simple[$bundle_name] = $bundle_info['label'];
      $bundle_options[$bundle_name] = [
        'label' => $bundle_info['label'],
        'enabled' => $this->configuration['target_bundles_drag_drop'][$bundle_name]['enabled'] ?? FALSE,
        'weight' => $this->configuration['target_bundles_drag_drop'][$bundle_name]['weight'] ?? $weight,
      ];
      $weight++;
    }

    // Do negate the selection.
    $form['negate'] = [
      '#type' => 'radios',
      '#options' => [
        1 => $this->t('Exclude the selected below'),
        0 => $this->t('Include the selected below'),
      ],
      '#title' => $this->t('Which Paragraph types should be allowed?'),
      '#default_value' => $this->configuration['negate'],
    ];

    // Kept for compatibility with other entity reference widgets.
    $form['target_bundles'] = [
      '#type' => 'checkboxes',
      '#options' => $bundle_options_simple,
      '#default_value' => $this->configuration['target_bundles'] ?? [],
      '#access' => FALSE,
    ];

    if ($bundle_options) {
      $form['target_bundles_drag_drop'] = [
        '#element_validate' => [[__CLASS__, 'targetTypeValidate']],
        '#type' => 'table',
        '#header' => [
          $this->t('Type'),
          $this->t('Weight'),
        ],
        '#attributes' => [
          'id' => 'bundles',
        ],
        '#prefix' => '<h5>' . $this->t('Paragraph types') . '</h5>',
        '#suffix' => '<div class="description">' . $this->t('Selection of Paragraph types for this field. Select none to allow all Paragraph types.') . '</div>',
      ];

      $form['target_bundles_drag_drop']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'bundle-weight',
      ];
    }

    uasort($bundle_options, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $weight_delta = $weight;

    // Default weight for new items.
    $weight = count($bundles) + 1;
    foreach ($bundle_options as $bundle_name => $bundle_info) {
      $form['target_bundles_drag_drop'][$bundle_name] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
      ];

      $form['target_bundles_drag_drop'][$bundle_name]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $bundle_info['label'],
        '#title_display' => 'after',
        '#default_value' => $bundle_info['enabled'],
      ];

      $form['target_bundles_drag_drop'][$bundle_name]['weight'] = [
        '#type' => 'weight',
        '#default_value' => (int) $bundle_info['weight'],
        '#delta' => $weight_delta,
        '#title' => $this->t('Weight for type @type', ['@type' => $bundle_info['label']]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['bundle-weight', 'bundle-weight-' . $bundle_name],
        ],
      ];
      $weight++;
    }

    if (empty($bundle_options)) {
      $form['allowed_bundles_explain'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You did not add any Paragraph types yet, click <a href=":here">here</a> to add one.', [':here' => Url::fromRoute('paragraphs.type_add')->toString()]),
      ];
    }

    return $form;
  }

  /**
   * Validate helper to have support for other entity reference widgets.
   *
   * @param array $element
   *   An element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state.
   * @param array $form
   *   A form array.
   */
  public static function targetTypeValidate(array $element, FormStateInterface $form_state, array $form) {
    $values = &$form_state->getValues();
    $element_values = NestedArray::getValue($values, $element['#parents']);
    $bundle_options = [];

    if ($element_values) {
      $enabled = 0;
      foreach ($element_values as $machine_name => $bundle_info) {
        if (isset($bundle_info['enabled']) && $bundle_info['enabled']) {
          $bundle_options[$machine_name] = $machine_name;
          $enabled++;
        }
      }

      // All disabled = all enabled.
      if ($enabled === 0) {
        $bundle_options = NULL;
      }
    }

    // New value parents.
    $parents = array_merge(array_slice($element['#parents'], 0, -1), ['target_bundles']);
    NestedArray::setValue($values, $parents, $bundle_options);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $values = [
      $entity_type->getKey('label') => $label,
    ];

    if ($bundle_key = $entity_type->getKey('bundle')) {
      $values[$bundle_key] = $bundle;
    }

    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    return array_filter($entities, function ($entity) {
      $target_bundles = $this->getConfiguration()['target_bundles'];
      if (isset($target_bundles)) {
        return in_array($entity->bundle(), $target_bundles);
      }
      return TRUE;
    });
  }

}
