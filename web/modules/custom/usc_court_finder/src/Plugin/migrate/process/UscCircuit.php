<?php

namespace Drupal\usc_court_finder\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * UscCircuit process plugin.
 *
 * Handle special circuits.
 *
 * @MigrateProcessPlugin(
 *   id = "usc_circuit"
 * )
 */
class UscCircuit extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (is_array($value) && count($value) === 2) {
      $org_name = $value[1];

      if ($org_name) {
        switch ($org_name) {

          case 'Supreme Court of the United States':
          case 'Federal Circuit Clerk/Executive':
          case 'Federal Circuit Librarian':
          case 'United States Court of Appeals for Veterans Claims':
          case 'United States Court of Appeals for the Armed Forces':
          case 'Judicial Panel On Multidistrict Litigation':
          case 'United States Court of Federal Claims':
          case 'United States Court of International Trade':
          case 'United States Court of International Trade Librarian':
          case 'United States Tax Court':
          case 'Federal Judicial Center':
          case 'United States Sentencing Commission':
            return 'OC';
        }
      }
    }

    if (!empty($value[0])) {
      return $value[0];
    }

    return '';
  }

}
