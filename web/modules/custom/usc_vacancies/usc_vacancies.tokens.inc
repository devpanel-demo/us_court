<?php

/**
 * @file
 * Token integration for the usc_vacancies module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function usc_vacancies_token_info() {
  $info['tokens']['view']['total-nominees-pending'] = [
    'name' => t('Total nominees pending'),
    'description' => t('The total nominees pending extracted from the corresponding judicial vacancy summary.'),
  ];
  $info['tokens']['view']['total-future-nominees-pending'] = [
    'name' => t('Total future nominees pending'),
    'description' => t('The total future nominees pending extracted from the corresponding judicial vacancy summary.'),
  ];
  $info['tokens']['view']['latest_job_node'] = [
    'name' => t('Latest Job Node ID'),
    'description' => t('The node ID of the latest job node.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function usc_vacancies_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  /*
   * We need the corresponding judicial summary node based on the archive id of the node being viewed.
   */
  if ($type == 'view' && !empty($data['view']) && $data['view']->id() == 'vacancies_judicial') {
    $archive_id = $current_pending_nominees = $future_pending_nominees = '';
    $view = $data['view'];
    $display = $view->current_display;
    if (isset($view->args) && !empty($view->args)) {
      $archive_id = $view->args[0];
    }

    if (!empty($archive_id)) {
      $summary_ids = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'vacancy')
        ->condition('field_vacancy_type', 'judicial_summary')
        ->condition('field_archive_id', $archive_id)
        ->execute();

      if (!empty($summary_ids)) {
        $summary = \Drupal::entityTypeManager()->getStorage('node')->load(reset($summary_ids));
        $refs = $summary->get('field_vacancy_reference')->referencedEntities();
        if ($refs) {
          foreach ($refs as $ref) {
            if ($ref->get('field_court_type')->value == 'Total') {
              $current_pending_nominees = $ref->get('field_pending')->value;
              $future_pending_nominees = $ref->get('field_future_pending')->value;
            }
          }

        }
      }
    }

    $bubbleable_metadata->addCacheableDependency($view->storage);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'total-nominees-pending':
          $replacements[$original] = (int) $current_pending_nominees;
          break;

        case 'total-future-nominees-pending':
          $replacements[$original] = (int) $future_pending_nominees;
          break;
      }
    }
  }
  if ($type == 'view') {
    foreach ($tokens as $name => $original) {
      if ($name === 'latest_job_node') {
        // Query the latest vacancy node.
        $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
        $query->condition('type', 'vacancy')
          ->condition('field_archive_id', 'usc_job')
          ->sort('nid', 'DESC')
          ->accessCheck(TRUE)
          ->range(0, 1);
        // @phpstan-ignore-next-line
        $nids = $query->execute();

        // Set the token to the latest node ID if found, otherwise set NULL.
        $replacements[$original] = !empty($nids) ? reset($nids) : '';
      }
    }
  }
  return $replacements;
}
