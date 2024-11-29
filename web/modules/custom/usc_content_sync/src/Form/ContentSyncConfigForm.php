<?php

namespace Drupal\usc_content_sync\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides config form for US Courts Vacancies.
 */
class ContentSyncConfigForm extends ConfigFormBase {

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value store factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, KeyValueFactoryInterface $key_value_factory) {
    parent::__construct($config_factory);

    $this->dateFormatter = $date_formatter;
    $this->keyValue = $key_value_factory->get('migrate_last_imported');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('keyvalue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usc_content_sync_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usc_content_sync.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('usc_content_sync.settings');

    $form['information'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Content information comes from YML files and assets exported by the simple content sync module.'),
    ];

    $form['usc_content_sync_vars'] = [
      '#type' => 'details',
      '#title' => $this->t('USC Content Sync Variables'),
      '#open' => FALSE,
    ];

    $form['usc_content_sync_vars']['usc_cs_import_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('USC Sync folder'),
      '#description' => $this->t('Enter either an absolute path or a path relative to the drupal directory.'),
      '#default_value' => $config->get('usc_cs_import_folder'),
      '#required' => TRUE,
    ];

    $form['usc_content_sync_vars']['usc_cs_content_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('USC Content directory'),
      '#description' => $this->t('Directory name with no trailing slash.'),
      '#default_value' => $config->get('usc_cs_content_directory'),
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
    $config = $this->config('usc_content_sync.settings');
    $config->set('usc_cs_import_folder', $form_state->getValue('usc_cs_import_folder'))
      ->set('usc_cs_content_directory', $form_state->getValue('usc_cs_content_directory'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
