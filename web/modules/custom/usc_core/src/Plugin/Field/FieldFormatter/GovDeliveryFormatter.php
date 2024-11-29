<?php

namespace Drupal\usc_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\usc_core\Form\GovDeliveryForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the GovDelivery formatter.
 *
 * @FieldFormatter(
 *   id = "gov_delivery_formatter",
 *   label = @Translation("GovDelivery Formatter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class GovDeliveryFormatter extends FormatterBase {

  /**
   * Constructs a CustomFormFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, private readonly FormBuilderInterface $formBuilder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $text_value = $item->value;
      $topic_id = $text_value ?? '';

      // phpcs:disable
      /* @phpstan-ignore-next-line */
      $form = $this->formBuilder->getForm(GovDeliveryForm::class, $topic_id);
      // phpcs:enable

      $elements[$delta]['form'] = $form;

    }

    return $elements;
  }

}
