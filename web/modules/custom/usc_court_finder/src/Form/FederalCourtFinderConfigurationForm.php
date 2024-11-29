<?php

namespace Drupal\usc_court_finder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for FCF links and configuration.
 */
class FederalCourtFinderConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'usc_court_finder.configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usc_court_finder.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('usc_court_finder.configuration');

    $form['fcf_court_type_help'] = [
      '#type' => 'linkit',
      '#title' => $this->t('Link to the court type help definition page'),
      '#default_value' => $config->get('fcf_court_type_help'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'default',
      ],
    ];

    $form['fcf_districts_help'] = [
      '#type' => 'linkit',
      '#title' => $this->t('Link to the districts help definition page'),
      '#default_value' => $config->get('fcf_districts_help'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'default',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->config('usc_court_finder.configuration')
      ->set('fcf_court_type_help', $form_state->getValue('fcf_court_type_help'))
      ->set('fcf_districts_help', $form_state->getValue('fcf_districts_help'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
