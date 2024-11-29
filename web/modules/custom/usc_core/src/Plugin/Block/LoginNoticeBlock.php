<?php

namespace Drupal\usc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LoginNoticeBlock' block.
 *
 * @Block(
 *   id = "login_notice_block",
 *   admin_label = @Translation("Login Notice"),
 * )
 */
class LoginNoticeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new LoginNoticeBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['content'] = [
      '#type' => 'component',
      '#component' => 'uscgov:callout',
      '#props' => [
        'callout_title' => $this->configuration['usc_login_notice_title'],
        'callout_body' => $this->configuration['usc_login_notice_description']
      ]
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['usc_login_notice_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notice Title'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['usc_login_notice_title'] ?? 'NOTICE TO USERS',
    ];

    $form['usc_login_notice_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notice Description'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['usc_login_notice_description'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['usc_login_notice_title'] = $form_state->getValue('usc_login_notice_title');
    $this->configuration['usc_login_notice_description'] = $form_state->getValue('usc_login_notice_description');
  }

}
