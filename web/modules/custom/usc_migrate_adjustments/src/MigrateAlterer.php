<?php

declare(strict_types=1);

namespace Drupal\usc_migrate_adjustments;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class to alter migrate upgrade generated files.
 */
final class MigrateAlterer {

  /**
   * Constructs a MigrateAlterer object.
   */
  public function __construct() {}

  /**
   * Processes migration plugins.
   *
   * @param array $migrations
   *   The array of migration plugins.
   */
  public function process(array &$migrations): void {
    $this->skipMigrations($migrations);
  }

  /**
   * Skips unneeded migrations.
   *
   * @param array $migrations
   *   The array of migration plugins.
   */
  private function skipMigrations(array &$migrations) {
    $migrations_to_skip = [
      'action_settings',
      'd7_action',
      'd7_block',
      'd7_global_theme_settings',
      'd7_image_settings',
      'd7_system_cron',
      'd7_system_mail',
      'd7_system_performance',
      'd7_theme_settings',
      'd7_user_flood',
      'd7_user_mail',
      'd7_user_settings',
      'd7_menu',
      'd7_menu_links',
      'd7_system_authorize',
      'file_settings',
      'system_image',
      'system_image_gd',
      'system_logging',
      'system_maintenance',
      'system_rss',
      'text_settings',
      'user_picture_entity_form_display',
      'user_profile_field_instance',
      'user_profile_field',
      'd7_view_modes',
      'upgrade_d7_view_modes',
      'upgrade_d7_user_role',
      'upgrade_d7_block',
      'd7_field_formatter_settings',
      'upgrade_d7_field_formatter_settings',
      'block_content_entity_form_display',
      'upgrade_d7_field_instance_widget_settings',
      'block_content_type',
      'block_content_entity_display',
      'block_content_body_field',
      'upgrade_d7_node_type',
      'upgrade_d7_node_complete_criminogenic',
      'upgrade_d7_node_complete_faq',
      'upgrade_d7_node_complete_usc_alert',
      'upgrade_d7_node_complete_user_alert',
      'upgrade_d7_node_complete_front_promo_box',
      'upgrade_d7_file_entity_image_https',
      'upgrade_d7_file_entity_image_http',
      'upgrade_d7_file_entity_document_http',
      'upgrade_d7_file_entity_file_public',
      'upgrade_d7_file_entity_type_remote_video',
      'upgrade_d7_file_entity_type_document',
      'upgrade_d7_file_entity_type_image',
      'upgrade_d7_field_collection_type',
      'upgrade_d7_file_entity_source_field_audio',
      'upgrade_d7_file_entity_type_audio',
      'upgrade_d7_file_entity_source_field_config_audio',
      'upgrade_d7_field_instance'
    ];

    $migrations = array_filter($migrations, function ($migration) use ($migrations_to_skip) {
      // Skip all D6.
      if (substr($migration['id'], 0, 3) === 'd6_') {
        return FALSE;
      }
      // Skip specific ones.
      return !in_array($migration['id'], $migrations_to_skip);
    });
  }

}
