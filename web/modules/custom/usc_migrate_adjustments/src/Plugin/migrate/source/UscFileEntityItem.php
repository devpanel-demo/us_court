<?php

namespace Drupal\usc_migrate_adjustments\Plugin\migrate\source;

use Drupal\media_migration\Plugin\migrate\source\d7\FileEntityItem;

/**
 * USC File Entity Item source plugin.
 *
 * Extends Media Migration File Entity Item source plugin to bypass
 * MediaMigrationSubscriber::preImport() method as we do not need adjustments
 * it performs for Banner images migration.
 *
 * @MigrateSource(
 *   id = "usc_file_entity_item",
 *   source_module = "file_entity",
 * )
 */
class UscFileEntityItem extends FileEntityItem {

}
