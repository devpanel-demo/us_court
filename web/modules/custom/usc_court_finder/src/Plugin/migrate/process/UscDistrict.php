<?php

namespace Drupal\usc_court_finder\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * UscDistrict process plugin.
 *
 * Handle special districts.
 *
 * @MigrateProcessPlugin(
 *   id = "usc_district"
 * )
 */
class UscDistrict extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (is_array($value) && count($value) === 2) {
      $org_name = $value[1];

      if ($org_name) {
        switch ($org_name) {

          case 'Supreme Court of the United States':
            return 'OC1';

          case 'Federal Circuit Clerk/Executive':
          case 'Federal Circuit Librarian':
            return 'OC2';

          case 'United States Court of Appeals for Veterans Claims':
            return 'OC8';

          case 'United States Court of Appeals for the Armed Forces':
            return 'OC3';

          case 'Judicial Panel On Multidistrict Litigation':
            return 'OC6';

          case 'United States Court of Federal Claims':
            return 'OC4';

          case 'United States Court of International Trade':
          case 'United States Court of International Trade Librarian':
            return 'OC5';

          case 'United States Tax Court':
            return 'OC7';

          case 'Federal Judicial Center':
          case 'United States Sentencing Commission':
            return 'OC9';
        }
      }
    }

    if (!empty($value[0])) {
      return $value[0];
    }

    return '';
  }

}
