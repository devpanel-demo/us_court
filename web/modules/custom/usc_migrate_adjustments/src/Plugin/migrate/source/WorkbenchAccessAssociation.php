<?php

namespace Drupal\usc_migrate_adjustments\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Workbench Access Association Entity source plugin.
 *
 * @MigrateSource(
 *   id = "usc_workbench_access_association",
 *   source_module = "workbench_access",
 * )
 */
class WorkbenchAccessAssociation extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $db = $this->getDatabase();
    assert($db instanceof Connection);
    $options = [
      'fetch' => \PDO::FETCH_ASSOC,
    ];
    $query = $db->select('workbench_access', 'wa', $options);
    $query->fields('wa');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'access_id' => $this->t('The association identifier'),
      'access_scheme' => $this->t('The association scheme'),
      'access_type' => $this->t('The association type'),
      'access_type_id' => $this->t('The association type identifier'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['access_id']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {

    $db = $this->getDatabase();
    assert($db instanceof Connection);
    $options = [
      'fetch' => \PDO::FETCH_ASSOC,
    ];

    // Get Roles.
    $query = $db->select('workbench_access_role', 'war', $options);
    $query->addField('war', 'rid');
    $access_id = $row->getSourceProperty('access_id');
    $query->condition('war.access_id', $access_id);
    $result = $query->execute();
    $roles = [];
    foreach ($result->fetchAll() as $role) {
      $roles[] = $role;
    }
    $row->setSourceProperty('roles', $roles);

    // Get Users.
    $query = $db->select('workbench_access_user', 'wau', $options);
    $query->addField('wau', 'uid');
    $access_id = $row->getSourceProperty('access_id');
    $query->condition('wau.access_id', $access_id);
    $result = $query->execute();
    $users = [];
    foreach ($result->fetchAll() as $user) {
      $users[] = $user;
    }
    $row->setSourceProperty('users', $users);

    return parent::prepareRow($row);
  }

}
