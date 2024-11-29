<?php

namespace Drupal\usc_court_finder\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * UscUnit process plugin.
 *
 * Extract the Unit from the OrgCode.
 *
 * @MigrateProcessPlugin(
 *   id = "usc_unit"
 * )
 */
class UscUnit extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if ($value && strlen($value) === 7) {
      return substr($value, 0, 1) . substr($value, 6, 1);
    }

    return '';
  }

}
