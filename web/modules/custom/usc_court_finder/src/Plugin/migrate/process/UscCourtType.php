<?php

namespace Drupal\usc_court_finder\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * UscCourtType process plugin.
 *
 * Extract the CourtType from the OrgCode.
 *
 * @MigrateProcessPlugin(
 *   id = "usc_court_type"
 * )
 */
class UscCourtType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if ($value && strlen($value) === 7) {
      $code = substr($value, 0, 1) . substr($value, 6, 1);

      switch ($code) {

        case 'AC':
        case 'AL':
        case 'AB':
          return 'Appeals Court';

        case 'BC':
        case 'BB':
          return 'Bankruptcy Court';

        case 'DC':
          return 'District Court';

        case 'DP':
        case 'DS':
          return 'Probation/Pretrial Services';

        case 'FF':
        case 'GF':
          return 'Federal Defenders';
      };
    }

    return '';
  }

}
