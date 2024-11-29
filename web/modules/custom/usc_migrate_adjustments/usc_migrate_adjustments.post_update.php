<?php

/**
 * @file
 * Post update functions for US Courts migration helper.
 */

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\Yaml\Yaml;

/**
 * Updates for USCGOV-601.
 *
 * Truncate m_map_upgrade_d7_file_entity_audio_public table.
 */
function usc_migrate_adjustments_post_update_10001(&$sandbox) {
  $connection = \Drupal::database();

  if ($connection->schema()->tableExists('m_map_upgrade_d7_file_entity_audio_public')) {
    $connection->truncate('m_map_upgrade_d7_file_entity_audio_public')->execute();
    \Drupal::messenger()->addMessage(t('Table m_map_upgrade_d7_file_entity_audio_public has been truncated.'));
  }
  else {
    \Drupal::messenger()->addMessage(t('Table does not exist. Skipping.'));
  }
}

/**
 * Updates for USCGOV-729.
 *
 * Truncate m_map_upgrade_d7_file_entity_banner_image_public table.
 */
function usc_migrate_adjustments_post_update_10002(&$sandbox) {
  $connection = \Drupal::database();

  if ($connection->schema()->tableExists('m_map_upgrade_d7_file_entity_banner_image_public')) {
    $connection->truncate('m_map_upgrade_d7_file_entity_banner_image_public')->execute();
    \Drupal::messenger()->addMessage(t('Table m_map_upgrade_d7_file_entity_banner_image_public has been truncated.'));
  }
  else {
    \Drupal::messenger()->addMessage(t('Table does not exist. Skipping.'));
  }
}

/**
 * Updates for USCGOV-902.
 *
 * Fixing duplicated nodes.
 *
 * @throws \Exception
 * @throws \DivisionByZeroError
 */
function usc_migrate_adjustments_post_update_10003(&$sandbox) {
  // The UUIDs of the nodes to delete.
  $uuids = [
    'a8753e94-37f4-4fb4-b465-967ca6b6892a',
    'f79e5038-b6bc-4b61-887f-a3e0559d898a',
    '4ea5a31a-e67c-4f04-9d29-a5a41d6ddaba',
    '85921708-c38d-4af1-95e1-d875c7ff887e',
    '4faba618-491a-44d3-8df9-c3ea8c88c8e3',
    'aef649ad-06e7-45ce-bb1d-fdc7fb4880bc',
    'f9c5dae8-9da9-494c-ad26-fa4d0d12a172',
    '23f263f5-f441-4df6-8d5a-c7639c53dcc4',
    '9d06824f-cb9b-4871-8fa2-8591c2d44445',
    '534d2389-f4cf-422d-ab6e-7bbd8b408bf4',
    '294369f8-2d8d-4831-bddc-00fe101636e9',
    '294f1c1f-d4a3-4470-8d72-09627b35abf1',
    '5787963b-5fd0-4bda-b432-d198eda7335e'
  ];

  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['total'] = count($uuids);
  }

  $batch_size = 1;
  $processed = 0;

  // Load and delete nodes by UUID.
  foreach (array_slice($uuids, $sandbox['progress'], $batch_size) as $uuid) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $uuid]);
    if ($node) {
      $node = reset($node);
      $node->delete();
    }
    $sandbox['progress']++;
    $processed++;
  }

  // Check if the update process is finished.
  if ($sandbox['progress'] < $sandbox['total']) {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
  }
  else {
    $sandbox['#finished'] = 1;
  }
}

/**
 * Updates for USCGOV-902.
 *
 * Fixing duplicated period_time taxonomy terms.
 *
 * @param array $sandbox
 *   The batch process sandbox array.
 *
 * @throws \Exception
 * @throws \DivisionByZeroError
 */
function usc_migrate_adjustments_post_update_10004(&$sandbox) {
  if (!isset($sandbox['step'])) {
    // Step 1: Initialize the process and collect nodes to update.
    $directory = realpath(DRUPAL_ROOT . '/../content_default/prod/taxonomy_term');
    if (!is_dir($directory)) {
      throw new Exception("Directory not found: " . $directory);
    }

    $files = glob($directory . '/*.yml');
    $original_uuids = [];
    foreach ($files as $file) {
      $data = Yaml::parseFile($file);
      if (isset($data['entity_type']) && $data['entity_type'] == 'taxonomy_term' && isset($data['bundle']) && $data['bundle'] == 'period_time') {
        $original_uuids[] = $data['uuid'];
      }
    }

    $name_mappings = [
      '1 Month' => '1m',
      '12 Months' => '12m',
      '2 Years' => '2y',
      '3 Months' => '3m',
      '5 Years' => '5y',
      '6 Years or more' => '6y',
      'Pending' => 'p',
    ];

    $nodes_to_update = [];
    foreach ($original_uuids as $original_uuid) {
      $original_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['uuid' => $original_uuid]);
      $original_term = reset($original_terms) ?: NULL;

      if ($original_term) {
        $original_name = $original_term->getName();
        $bundle = $original_term->bundle();
        $field_name = 'field_reporting_period_increment';

        $duplicate_terms = _usc_migrate_adjustments_find_duplicates($original_name, $name_mappings, $bundle, $original_uuid);

        foreach ($duplicate_terms as $duplicate_term) {
          $nodes = _usc_migrate_adjustments_find_nodes_by_term($duplicate_term, ['data_table'], $field_name);
          foreach ($nodes as $node) {
            $nodes_to_update[] = [
              'node_id' => $node->id(),
              'duplicate_term_id' => $duplicate_term->id(),
              'original_term_id' => $original_term->id(),
              'field_name' => $field_name,
            ];
          }

          \Drupal::logger('usc_core')->notice('Identified term @old_uuid to be replaced with @new_uuid in field @field_name of bundle @bundle.', [
            '@old_uuid' => $duplicate_term->uuid(),
            '@new_uuid' => $original_uuid,
            '@field_name' => $field_name,
            '@bundle' => 'data_table',
          ]);

          $duplicate_term->delete();
          \Drupal::logger('usc_core')->notice('Deleted duplicate term @uuid.', [
            '@uuid' => $duplicate_term->uuid(),
          ]);
        }
      }
    }

    // Initialize sandbox for step 2.
    $sandbox['nodes_to_update'] = $nodes_to_update;
    $sandbox['progress'] = 0;
    $sandbox['total'] = count($nodes_to_update);
    $sandbox['step'] = 2;
  }

  if ($sandbox['step'] == 2) {
    // Step 2: Update the nodes in smaller batches.
    $batch_size = 10;
    $processed = 0;

    while ($processed < $batch_size && $sandbox['progress'] < $sandbox['total']) {
      $node_info = $sandbox['nodes_to_update'][$sandbox['progress']];
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_info['node_id']);

      if ($node) {
        $field_items = $node->get($node_info['field_name'])->getValue();
        foreach ($field_items as &$item) {
          if ($item['target_id'] == $node_info['duplicate_term_id']) {
            $item['target_id'] = $node_info['original_term_id'];
          }
        }
        $node->set($node_info['field_name'], $field_items);
        $node->save();

        \Drupal::logger('usc_core')->notice('Updated node @node_id: replaced term @old_term_id with @new_term_id in field @field_name.', [
          '@node_id' => $node->id(),
          '@old_term_id' => $node_info['duplicate_term_id'],
          '@new_term_id' => $node_info['original_term_id'],
          '@field_name' => $node_info['field_name'],
        ]);
      }

      $sandbox['progress']++;
      $processed++;
    }

    // Check if the update process is finished.
    if ($sandbox['progress'] < $sandbox['total']) {
      $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
    }
    else {
      $sandbox['#finished'] = 1;
    }
  }
}

/**
 * Find duplicate taxonomy terms by name or name mapping.
 *
 * @param string $original_name
 *   The original name of the taxonomy term.
 * @param array $name_mappings
 *   An associative array of name mappings.
 * @param string $bundle
 *   The bundle of the taxonomy term.
 * @param string $original_uuid
 *   The UUID of the original taxonomy term to exclude.
 *
 * @return \Drupal\taxonomy\Entity\Term[]
 *   An array of duplicate taxonomy term entities.
 */
function _usc_migrate_adjustments_find_duplicates($original_name, $name_mappings, $bundle, $original_uuid) {
  $duplicates = [];
  $query = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', $bundle)
    ->accessCheck(FALSE);
  $tids = $query->execute();

  if ($tids) {
    $terms = Term::loadMultiple($tids);
    foreach ($terms as $term) {
      if ($term->uuid() != $original_uuid && ($term->getName() == $original_name || $term->getName() == ($name_mappings[$original_name] ?? ''))) {
        $duplicates[] = $term;
      }
    }
  }
  return $duplicates;
}

/**
 * Find nodes that reference a specific taxonomy term.
 *
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The taxonomy term entity.
 * @param array $bundles
 *   An array of node bundle types to search within.
 * @param string $field_name
 *   The field name that references the taxonomy term.
 *
 * @return \Drupal\node\Entity\Node[]
 *   An array of node entities that reference the taxonomy term.
 */
function _usc_migrate_adjustments_find_nodes_by_term($term, $bundles, $field_name) {
  $query = \Drupal::entityQuery('node')
    ->condition('type', $bundles, 'IN')
    ->condition($field_name, $term->id())
    ->accessCheck(FALSE);
  $nids = $query->execute();
  return Node::loadMultiple($nids);
}

/**
 * Updates for USCGOV-902.
 *
 * Delete duplicated taxonomy terms added by test content.
 *
 * @throws \Exception
 */
function usc_migrate_adjustments_post_update_10005() {
  $file = DRUPAL_ROOT . '/modules/custom/usc_migrate_adjustments/duplicated_terms.yml';
  $data = Yaml::parseFile($file);

  foreach ($data as $entry) {
    if (isset($entry['new_uuid']) && $entry['uuid'] !== $entry['new_uuid']) {
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['uuid' => $entry['uuid']]);
      $term = reset($terms) ?: NULL;
      if ($term) {
        $term->delete();
        \Drupal::logger('usc_migrate_adjustments')->notice("Deleted term with UUID '{$entry['uuid']}'");
      }
      else {
        \Drupal::logger('usc_migrate_adjustments')->notice("Term not found for UUID '{$entry['uuid']}'");
      }
    }
  }
}
