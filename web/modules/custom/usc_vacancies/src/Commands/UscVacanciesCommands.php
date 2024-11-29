<?php

namespace Drupal\usc_vacancies\Commands;

use Drupal\audiofield\AudioFieldPlayerManager;
use Drupal\usc_vacancies\VacancyImportService;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file for USCourts Vacancies.
 */
class UscVacanciesCommands extends DrushCommands {

  /**
   * The vacancy service.
   *
   * @var \Drupal\usc_vacancies\VacancyImportService
   */
  protected $vacancyImportService;

  /**
   * {@inheritdoc}
   */
  public function __construct(VacancyImportService $vacancy_import_service) {
    parent::__construct();

    $this->vacancyImportService = $vacancy_import_service;
  }

  /**
   * Imports Judicial Vacancies for a particular month.
   *
   * @param null $year_month
   *   The YYYY_MM to import the judicial vacancies for.
   *
   * @usage drush usc:importJudicial YYYY_MM
   *    Imports judicial vacancies for the given month. Leave blank to use current date.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \ValueError
   *
   * @command usc:importJudicial
   * @aliases uscjud
   */
  public function importJudicial($year_month = NULL) {
    if (is_null($year_month)) {
      $year_month = date('Y_m');
    }

    $this->output()->writeln("Importing vacancies for $year_month .");

    $completed = $this->vacancyImportService->importDataJudicial($year_month);
  }

  /**
   * Imports Judicial current month.
   *
   * @usage drush usc:importJudicialVacancies
   *    Imports judicial vacancies for the current month.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \ValueError
   *
   * @command usc:importJudicialVacancies
   * @aliases uscvacancies
   */
  public function importJudicialCurrent() {
    $this->output()->writeln("Importing vacancies from current directory.");

    $completed = $this->vacancyImportService->importCurrentJudicial();
  }

  /**
   * Imports Job Vacancies.
   *
   * @usage drush usc:importJob
   *    Imports jobs using the file path from config.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \InvalidArgumentException
   *
   * @command usc:importJob
   * @aliases uscjob
   */
  public function importJob() {
    $this->output()->writeln("Importing jobs.");
    $completed = $this->vacancyImportService->importDataJob();
  }

  /**
   * Imports Judicial Vacancies archive.
   *
   * @usage drush usc:importJudicialArchive
   *    Imports jobs using the directory path from config.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \InvalidArgumentException
   * @throws \ValueError
   *
   * @command usc:importJudicialArchive
   * @aliases uscjudarc
   */
  public function importJudicialArchive() {
    $this->output()->writeln("Importing vacancies archive.");
    $completed = $this->vacancyImportService->importDataJudicialArchive();
  }

}
