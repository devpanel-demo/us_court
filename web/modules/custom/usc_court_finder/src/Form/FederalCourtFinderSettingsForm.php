<?php

declare(strict_types=1);

namespace Drupal\usc_court_finder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for a court finder location entity type.
 */
final class FederalCourtFinderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'usc_court_finder_federal_court_finder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usc_court_finder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('usc_court_finder.settings');

    $form['settings'] = [
      '#markup' => $this->t('Settings form for a court finder location entity type.'),
    ];

    $form['location_source_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location Source URL'),
      '#description' => $this->t('Enter either an absolute path or a path relative to the drupal directory.'),
      '#default_value' => $config->get('location_source_url'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $config = $this->config('usc_court_finder.settings');
    $config->set('location_source_url', $form_state->getValue('location_source_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
