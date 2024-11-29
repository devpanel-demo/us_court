<?php

namespace Drupal\usc_core\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Custom date views filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("custom_date")
 */
class ViewsCustomFilterDate extends FilterPluginBase {

  /**
   * Determine if a filter can be converted into a group.
   */
  protected function canBuildGroup() {
    return $this->isExposed();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $field_name = $this->options['custom_date_field_name'];
    $date_format = $this->options['custom_date_format'];
    $operator = $this->options['operator'];

    if (!$this->options['exposed']) {
      // Administrative value.
      $date = $this->options['custom_date'];
    }
    else {
      // Exposed value.
      if (empty($this->value) || empty($this->value[0])) {
        return;
      }
      $date = $this->value[0];
    }

    // phpcs:disable
    /** @phpstan-ignore-next-line */
    $this->query->addTable("node__{$field_name}");
    /** @phpstan-ignore-next-line */
    $this->query->addWhereExpression(0, "date_format(node__{$field_name}.{$field_name}_value, '{$date_format}') {$operator} '{$date}'", []);
    // phpcs:enable
  }

  /**
   * Provide a list of all supported operators.
   */
  public function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'method' => 'opSimple',
        'short' => $this->t('!='),
        'values' => 1,
      ],
    ];

    return $operators;
  }

  /**
   * Provide a list of all the operators as options.
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Provide the operator value.
   */
  protected function operatorValues($values = 1) {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if ($info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom date'),
        '#description' => $this->t('Enter a date in the custom format'),
        '#default_value' => '',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['custom_date'] = ['default' => ''];
    $options['custom_date_field_name'] = ['default' => ''];
    $options['custom_date_format'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['custom_date_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use Node Granular Date filter with this field name (enter machine name)'),
      '#description' => $this->t('Machine field names appear on content types field list (e.g. field_fecha_blog).'),
      '#default_value' => $this->options['custom_date_field_name'] ?? NULL,
      '#required' => TRUE,
    ];

    $form['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('See <a href="https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format" target="_blank">the documentation for MySQL date formats</a>.'),
      '#default_value' => $this->options['custom_date_format'] ?? NULL,
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if ($this->options['exposed']) {
      $variables = [
        '@field' => $this->options['custom_date_field_name'],
      ];
      return $this->t('Exposed on field "@field"', $variables);
    }

    // Administrative filter.
    $variables = [
      '@custom_date' => $this->options['custom_date'],
      '@field' => $this->options['custom_date_field_name'],
    ];
    return $this->t('Filter on field "@field" [@custom_date (date)] ', $variables);
  }

}
