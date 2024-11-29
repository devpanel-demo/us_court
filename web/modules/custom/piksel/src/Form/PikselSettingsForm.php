<?php

namespace Drupal\piksel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure piksel settings for this site.
 */
class PikselSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'piksel.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piksel_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['piksel_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API endpoint'),
      '#default_value' => $config->get('piksel_endpoint'),
    ];

    $form['piksel_player_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Piksel Player URL'),
      '#default_value' => $config->get('piksel_player_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('piksel_endpoint', $form_state->getValue('piksel_endpoint'))
      ->set('piksel_player_url', $form_state->getValue('piksel_player_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
