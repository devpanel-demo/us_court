<?php

namespace Drupal\gutenberg_uswds_collection;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 *
 * @internal
 */
class EntityTypeConfig implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * EntityTypeConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Alters bundle forms to enforce revision handling.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see hook_form_alter()
   */
  public function formNodeTypeAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if ($form_id == 'node_type_edit_form' || $form_id == 'node_type_add_form') {
      $config = $this->configFactory->get('gutenberg_uswds_collection.settings');
      $allowed_content_embed = $config->get('allowed_uswds_collection_embed');
      $types = [];
      $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      foreach ($content_types as $content_type) {
        $types[$content_type->id()]['label'] = $content_type->label();
        $types[$content_type->id()]['view_modes'] = $this->entityDisplayRepository->getViewModeOptionsByBundle('node', $content_type->id());
      }
      // Avoid the rest of the code if no content types found.
      if (empty($types)) {
        return;
      }

      $form['gutenberg']['allowed_uswds_collection_details'] = [
        '#type' => 'details',
        '#title' => $this->t('Allowed Collections'),
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            'input[name="enable_gutenberg_experience"]' => ['checked' => TRUE],
          ],
        ],
      ];
      foreach ($types as $content_type_id => $content_type_info) {
        $form['gutenberg']['allowed_uswds_collection_details'][$content_type_id] = [
          '#type' => 'fieldset',
          '#title' => $content_type_info['label'],
        ];
        $form['gutenberg']['allowed_uswds_collection_details'][$content_type_id]['label'] = [
          '#type' => 'value',
          '#value' => $content_type_info['label'],
        ];
        $form['gutenberg']['allowed_uswds_collection_details'][$content_type_id]['allowed_uswds_collection_view_modes'] = [
          '#type' => 'checkboxes',
          '#title' => t('Allowed View Modes'),
          '#options' => $content_type_info['view_modes'],
          '#default_value' => $allowed_content_embed[$content_type_id]['allowed_uswds_collection_view_modes'] ?? [],
          '#description' => $this->t('Select the allowed view modes for this content type, if none selected then this content type will not appear available to embed.'),
        ];
      }
      // Adding submit to update config.
      $form['actions']['submit']['#submit'][] = [$this, 'formNodeTypeSubmit'];

      if (isset($form['actions']['save_continue']['#submit'])) {
        $form['actions']['save_continue']['#submit'][] = [
          $this,
          'formNodeTypeSubmit',
        ];
      }
    }
  }

  /**
   * Alters the node form submit.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function formNodeTypeSubmit(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('gutenberg_uswds_collection.settings');
    $config->set('allowed_uswds_collection_embed', $form_state->getValue('allowed_uswds_collection_details'))->save();
  }

  /**
   * Alters the node edit form.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function formNodeAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityForm $form_object */
    $form_object = $form_state->getFormObject();
    $node = $form_object->getEntity();

    if (!_gutenberg_is_gutenberg_enabled($node)) {
      // Leave early if Gutenberg is not enabled for this entity.
      return;
    }

    // Preparing settings to be sended to js.
    $config = $this->configFactory->get('gutenberg_uswds_collection.settings');
    $allowed_content_embed = $config->get('allowed_uswds_collection_embed');
    $filtered_content_embed = [];
    foreach ($allowed_content_embed as $content_type_id => $content_type_info) {
      // Allowed content types.
      $enabled_view_modes = array_filter($content_type_info['allowed_uswds_collection_view_modes']);
      if ($enabled_view_modes) {
        $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle('node', $content_type_id);
        $view_modes = array_intersect_key($view_modes, $enabled_view_modes);

        if ($content_type_id == 'news' && isset($view_modes['calendar'])) {
          $view_mode_calendar = $view_modes['calendar'];
          unset($view_modes['calendar']);
          $view_modes['calendar'] = $view_mode_calendar;
        }

        $filtered_content_embed[$content_type_id]['label'] = $content_type_info['label'];
        $filtered_content_embed[$content_type_id]['viewModes'] = $view_modes;
      }
    }

    // Sending the content types info to create the blocks.
    $form['#attached']['drupalSettings']['gutenbergCollectionEmbed']['allowed'] = $filtered_content_embed;
  }

}
