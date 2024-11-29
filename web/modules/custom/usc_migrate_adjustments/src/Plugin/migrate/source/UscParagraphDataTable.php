<?php

namespace Drupal\usc_migrate_adjustments\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\NodeComplete;

/**
 * Custom source plugin to fetch data from both field data and revision tables.
 *
 * @MigrateSource(
 *   id = "usc_paragraph_data_table",
 *   source_module = "node"
 * )
 */
class UscParagraphDataTable extends NodeComplete {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Add fields from both field_data and field_revision tables.
    $query = parent::query();
    $query->leftJoin('field_data_field_lead_paragraph', 'fdlp', 'n.vid = fdlp.revision_id');
    $query->leftJoin('field_revision_field_lead_paragraph', 'frlp', 'n.vid = frlp.revision_id');

    // Select the field data from the appropriate table.
    $query->addField('fdlp', 'field_lead_paragraph_value', 'lead_paragraph_data');
    $query->addField('frlp', 'field_lead_paragraph_value', 'lead_paragraph_revision');

    return $query;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    // Use the revision data if available, otherwise fall back to base data.
    if (!empty($row->getSourceProperty('lead_paragraph_revision'))) {
      $row->setSourceProperty('lead_paragraph', $row->getSourceProperty('lead_paragraph_revision'));
    }
    else {
      $row->setSourceProperty('lead_paragraph', $row->getSourceProperty('lead_paragraph_data'));
    }

    return parent::prepareRow($row);
  }

}
