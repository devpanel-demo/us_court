<?php

namespace Drupal\usc_vacancies\Form;

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
class VacanciesConfigForm extends ConfigFormBase {

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
    return 'usc_vacancies_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usc_vacancies.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('usc_vacancies.settings');

    $form['information'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Vacancy information originates via XML files that are parsed and imported as nodes.'),
    ];

    $form['usc_vacancy_vars'] = [
      '#type' => 'details',
      '#title' => $this->t('Vacancy Variables'),
      '#open' => FALSE,
    ];

    $form['usc_vacancy_vars']['usc_prefix_cache'] = [
      '#type' => 'textfield',
      '#title' => $this->t('USC Prefix Cache'),
      '#description' => $this->t('Enter the prefix for the caching variable.'),
      '#default_value' => $config->get('usc_prefix_cache'),
      '#required' => TRUE,
    ];

    $form['usc_vacancy_vars']['usc_prefix_judicial'] = [
      '#type' => 'textfield',
      '#title' => $this->t('USC Prefix Judicial'),
      '#description' => $this->t('Enter the prefix for the Judicial vacancies variable.'),
      '#default_value' => $config->get('usc_prefix_judicial'),
      '#required' => TRUE,
    ];

    $form['usc_judicial'] = [
      '#type' => 'details',
      '#title' => $this->t('Judicial Vacancy Sources (HRMIS)'),
    ];

    $form['usc_judicial']['usc_dir_judicial'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Directory (server path)'),
      '#description' => $this->t('Enter either an absolute path or a path relative to the drupal directory.'),
      '#default_value' => $config->get('usc_dir_judicial'),
      '#required' => TRUE,
    ];

    $form['usc_judicial']['usc_dir_judicial_archive'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Archive Directory (relative to base directory path)'),
      '#description' => $this->t('Directory name with no trailing slash.'),
      '#default_value' => $config->get('usc_dir_judicial_archive'),
      '#required' => TRUE,
    ];

    $form['usc_judicial']['judicial_summary'] = [
      '#type' => 'details',
      '#title' => $this->t('Summary'),
      '#open' => FALSE,
    ];

    $form['usc_judicial']['judicial_summary']['usc_dir_judicial_summary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Judicial Vacancies Summary'),
      '#description' => $this->t('Enter the file name only.'),
      '#default_value' => $config->get('usc_dir_judicial_summary'),
      '#required' => TRUE,
    ];

    $last_imported = $this->keyValue->get('usc_vacancy_judicial_summary_feed', FALSE);
    $form['usc_judicial']['judicial_summary']['usc_file_judicial_summary_last_import'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Last imported: @when', [
        '@when' => $this->dateFormatter->format((int) ($last_imported / 1000),
          'custom', 'Y-m-d H:i:s')
      ]),
    ];

    $form['usc_judicial']['judicial_current'] = [
      '#type' => 'details',
      '#title' => $this->t('Current'),
      '#open' => FALSE,
    ];

    $form['usc_judicial']['judicial_current']['usc_dir_judicial_vacancies'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current Judicial Vacancies'),
      '#description' => $this->t('Enter the file name only.'),
      '#default_value' => $config->get('usc_dir_judicial_vacancies'),
      '#required' => TRUE,
    ];

    $last_imported = $this->keyValue->get('usc_vacancy_judicial_current_feed', FALSE);
    $form['usc_judicial']['judicial_current']['usc_file_current_judicial_vacancies_last_import'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Last imported: @when', [
        '@when' => $this->dateFormatter->format((int) ($last_imported / 1000),
          'custom', 'Y-m-d H:i:s')
      ]),
    ];

    $form['usc_judicial']['judicial_future'] = [
      '#type' => 'details',
      '#title' => $this->t('Future'),
      '#open' => FALSE,
    ];

    $form['usc_judicial']['judicial_future']['usc_dir_judicial_future'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Future Judicial Vacancies'),
      '#description' => $this->t('Enter the file name only.'),
      '#default_value' => $config->get('usc_dir_judicial_future'),
      '#required' => TRUE,
    ];

    $last_imported = $this->keyValue->get('usc_vacancy_judicial_future_feed', FALSE);
    $form['usc_judicial']['judicial_future']['usc_file_future_judicial_vacancies_last_import'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Last imported: @when', [
        '@when' => $this->dateFormatter->format((int) ($last_imported / 1000),
          'custom', 'Y-m-d H:i:s')
      ]),
    ];

    $form['usc_judicial']['judicial_confirmations'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmations'),
      '#open' => FALSE,
    ];

    $form['usc_judicial']['judicial_confirmations']['usc_dir_judicial_confirmations'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation Listings'),
      '#description' => $this->t('Enter the file name only.'),
      '#default_value' => $config->get('usc_dir_judicial_confirmations'),
      '#required' => TRUE,
    ];

    $last_imported = $this->keyValue->get('usc_vacancy_judicial_confirmation_feed', FALSE);
    $form['usc_judicial']['judicial_confirmations']['usc_file_confirmation_judicial_vacancies_last_import'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Last imported: @when', [
        '@when' => $this->dateFormatter->format((int) ($last_imported / 1000),
          'custom', 'Y-m-d H:i:s')
      ]),
    ];

    $form['usc_judicial']['judicial_emergencies'] = [
      '#type' => 'details',
      '#title' => $this->t('Emgergencies'),
      '#open' => FALSE,
    ];

    $form['usc_judicial']['judicial_emergencies']['usc_dir_judicial_emergencies'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Judicial Emergency Vacancies'),
      '#description' => $this->t('Enter the file name only.'),
      '#default_value' => $config->get('usc_dir_judicial_emergencies'),
      '#required' => TRUE,
    ];

    $last_imported = $this->keyValue->get('usc_vacancy_judicial_emergency_feed', FALSE);
    $form['usc_judicial']['judicial_emergencies']['usc_file_emergency_judicial_vacancies_last_import'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Last imported: @when', [
        '@when' => $this->dateFormatter->format((int) ($last_imported / 1000),
          'custom', 'Y-m-d H:i:s')
      ]),
    ];

    $form['usc_job'] = [
      '#type' => 'details',
      '#title' => $this->t('Job Vacancy Sources (JNet)'),
    ];

    $form['usc_job']['usc_dir_job'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Directory (server path)'),
      '#description' => $this->t('Enter either an absolute path or a path relative to the drupal directory.'),
      '#default_value' => $config->get('usc_dir_job'),
      '#required' => TRUE,
    ];

    $form['usc_job']['usc_file_job_vacancies'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Job Vacancies'),
      '#description' => $this->t('Enter the file name only.'),
      '#default_value' => $config->get('usc_file_job_vacancies'),
      '#required' => TRUE,
    ];

    $last_imported = $this->keyValue->get('usc_job_notice_feed', FALSE);
    $form['usc_job']['usc_file_job_vacancies_last_import'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Last imported: @when', [
        '@when' => $this->dateFormatter->format((int) ($last_imported / 1000),
        'custom', 'Y-m-d H:i:s')
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('usc_vacancies.settings');
    $config->set('usc_dir_judicial', $form_state->getValue('usc_dir_judicial'))
      ->set('usc_dir_judicial_archive', $form_state->getValue('usc_dir_judicial_archive'))
      ->set('usc_dir_judicial_summary', $form_state->getValue('usc_dir_judicial_summary'))
      ->set('usc_dir_judicial_vacancies', $form_state->getValue('usc_dir_judicial_vacancies'))
      ->set('usc_dir_judicial_future', $form_state->getValue('usc_dir_judicial_future'))
      ->set('usc_dir_judicial_confirmations', $form_state->getValue('usc_dir_judicial_confirmations'))
      ->set('usc_dir_judicial_emergencies', $form_state->getValue('usc_dir_judicial_emergencies'))
      ->set('usc_dir_job', $form_state->getValue('usc_dir_job'))
      ->set('usc_file_job_vacancies', $form_state->getValue('usc_file_job_vacancies'))
      ->set('usc_prefix_cache', $form_state->getValue('usc_prefix_cache'))
      ->set('usc_prefix_judicial', $form_state->getValue('usc_prefix_judicial'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
