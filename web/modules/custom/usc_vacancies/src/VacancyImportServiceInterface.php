<?php

namespace Drupal\usc_vacancies;

/**
 * Service for importing vacancies data.
 */
interface VacancyImportServiceInterface {

  /**
   * Import Judicial vacancies data for a specific period.
   *
   * @param string $year_month
   *   A year/month in the form of YYYY_MM.
   *
   * @return bool
   *   Return successful or not.
   */
  public function importDataJudicial($year_month = NULL);

  /**
   * Import Job vacancies data.
   *
   * @return bool
   *   Return successful or not.
   */
  public function importDataJob();

}
