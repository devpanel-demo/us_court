<?php

namespace Drupal\usc_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\filter\FilterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin formatter to render legacy D7 Highcharts script fields.
 *
 * @FieldFormatter(
 *   id = "usc_highcharts_script_formatter",
 *   label = @Translation("Highcharts Script Formatter"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class HighchartsScriptFormatter extends FormatterBase {

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * Constructs a new YourClass object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\filter\FilterPluginManager $filter_plugin_manager
   *   The filter plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FilterPluginManager $filter_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->filterPluginManager = $filter_plugin_manager;
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
      $container->get('plugin.manager.filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      $script = $item->value;
      $format = $item->format;

      if (!empty($script) && !empty($format)) {

        $processed = $item->processed->__toString();

        // Replace renderTo container id with our own
        // Generate a unique HTML ID based on a string.
        $html_id = Html::getUniqueId('usc_highcharts');
        $processed = preg_replace('/renderTo: \'.*\'/', "renderTo: '$html_id'", $processed);

        // Build the render array for the theme.
        $elements[$delta] = [
          '#theme' => 'usc_highcharts_script',
          '#script' => $processed,
          '#id' => $html_id,
        ];
      }
    }

    return $elements;
  }

}
