<?php

namespace Drupal\usc_vacancies\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\usc_vacancies\VacancyImportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form to handle administrative functions.
 */
class VacanciesArchiveAdminForm extends FormBase {

  /**
   * The vacancy service.
   *
   * @var \Drupal\usc_vacancies\VacancyImportService
   */
  protected $vacancyImportService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an admin form.
   *
   * @param \Drupal\usc_vacancies\VacancyImportService $vacancy_import_service
   *   The vacancy service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(VacancyImportService $vacancy_import_service, MessengerInterface $messenger) {
    $this->vacancyImportService = $vacancy_import_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('usc_vacancies.import_service'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vacancies_archive_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => '<p>Use this form to manage the Judicial Vacancies Archives.</p>',
    ];

    $form['usc_vacancies_task'] = [
      '#type' => 'select',
      '#title' => t('Task'),
      '#options' => [
        0 => t('-- Select a task --'),
        1 => t('Clear Judicial Vacancies Archive Cache'),
        2 => t('Build Judicial Vacancies Archive Cache'),
        3 => t('Import Judicial Vacancies (HRMIS)'),
        4 => t('Import Job Vacancies (JNet)'),
      ],
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $dirs = $this->vacancyImportService->getJudicialArchiveDirs();
    $form['year_month'] = [
      '#type' => 'select',
      '#title' => t('Year/Month'),
      '#options' => $dirs,
      '#states' => [
        'visible' => [
          ':input[name="usc_vacancies_task"]' => ['value' => '3'],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \InvalidArgumentException
   * @throws \ValueError
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $completed = FALSE;
    $task = $form_state->getValue('usc_vacancies_task');

    switch ($task) {
      case 1:
        $completed = TRUE;
        $this->vacancyImportService->clearJudicialArchiveCache();
        break;

      case 2:
        $dirs = $this->vacancyImportService->scanJudicialArchiveDirs();
        if (!empty($dirs)) {
          $completed = TRUE;
        }
        break;

      case 3:
        $year_month = $form_state->getValue('year_month');
        $completed = $this->vacancyImportService->importDataJudicial($year_month);
        break;

      case 4: $completed = $this->vacancyImportService->importDataJob();
        break;

      default:
        break;
    }

    if ($completed) {
      $this->messenger->addMessage(t('Task ":task" completed successfully.', [':task' => $form['usc_vacancies_task']['#options'][$task]]), 'status');
    }
    elseif (!$task) {
      $this->messenger->addMessage(t('Please select a valid option.'), 'error');
    }
    else {
      $this->messenger->addMessage(t('Task ":task" failed!', [':task' => $form['usc_vacancies_task']['#options'][$task]]), 'error');
    }

  }

}
