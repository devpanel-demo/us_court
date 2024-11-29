<?php

namespace Drupal\usc_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for search.gov integration.
 */
class SearchConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usc_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('usc_search.settings');

    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search.gov URL'),
      '#default_value' => !empty($config->get('link')) ? $config->get('link') : '',
      '#description' => $this->t('The URL should include the search path e.g: https://search.uscourts.gov/search'),
      '#required' => TRUE,
    ];

    $form['affiliate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Affiliate'),
      '#default_value' => !empty($config->get('affiliate')) ? $config->get('affiliate') : '',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('usc_search.settings');
    $config->set('link', $form_state->getValue('link'));
    $config->set('affiliate', $form_state->getValue('affiliate'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
