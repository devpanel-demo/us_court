<?php

namespace Drupal\usc_failtest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures usc_failtest's settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usc_failtest_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'usc_failtest.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Provide a text field to receive the failtest message.
    $config = $this->config('usc_failtest.settings');
    $form['failtest_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Failtest message'),
      '#description' => $this->t('Enter a message that will be displayed at /failtest'),
      '#default_value' => $config->get('failtest_message'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('usc_failtest.settings')
      ->set('failtest_message', $form_state->getValue('failtest_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
