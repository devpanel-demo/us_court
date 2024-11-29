<?php

namespace Drupal\usc_content_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;
use Drupal\usc_content_sync\NodeSyncService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContentSyncController.
 */
class ContentSyncController extends ControllerBase {

  /**
   * The import service.
   *
   * @var \Drupal\usc_content_sync\NodeSyncService
   */
  protected $importService;

  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ContentSyncController object.
   *
   * @param \Drupal\usc_content_sync\NodeSyncService $import_service
   *   The USC content sync service.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(NodeSyncService $import_service, PagerManagerInterface $pager_manager, RequestStack $request_stack, RendererInterface $renderer) {
    $this->importService = $import_service;
    $this->pagerManager = $pager_manager;
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('usc_content_sync.sync_service'),
      $container->get('pager.manager'),
      $container->get('request_stack'),
      $container->get('renderer'),
    );
  }

  /**
   * Returns a table of YML files and their absolute paths with sorting.
   *
   * @return array
   *   A render array representing the table.
   *
   * @throws \Exception
   */
  public function overview() {
    $files = $this->importService->getFilesAndNodes();
    $files = $this->formatSynced($files);

    // Table header definition with sortable columns.
    $header = [
      ['data' => $this->t('Title'), 'field' => 'filename', 'sort' => 'asc'],
      ['data' => $this->t('Content Type'), 'field' => 'bundle'],
      ['data' => $this->t('Synced'), 'field' => 'synced'],
      ['data' => $this->t('Source UUID'), 'field' => 'uuid'],
      ['data' => $this->t('Actions')],
    ];

    // Prepare the rows.
    $rows = [];
    $source_changes = 0;
    $current_changes = 0;
    // phpcs:ignore
    // @phpstan-ignore-next-line
    foreach ($files as $file) {
      if (!empty($file['node'])) {
        $nid = $file['node']->id();
        // Generate URLs for View, Edit, and Delete actions.
        $view_url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
        $edit_url = Url::fromRoute('entity.node.edit_form', ['node' => $nid]);
        $delete_url = Url::fromRoute('usc_content_sync.exclude_confirmation_page', ['node' => $nid]);
        // Create a dropbutton render array with actions.
        $operations = [
          '#type' => 'dropbutton',
          '#attributes' => [
            'class' => ['dropbutton', 'dropbutton--extrasmall', 'dropbutton--multiple'],
          ],
          '#links' => [
            'view' => [
              'title' => t('View'),
              'url' => $view_url,
            ],
            'edit' => [
              'title' => t('Edit'),
              'url' => $edit_url,
            ],
            'delete' => [
              'title' => t('Exclude from Sync'),
              'url' => $delete_url,
              'attributes' => [
                'class' => ['button', 'button--danger'],
              ],
            ],
          ],
        ];
        $op = $this->renderer->render($operations);
        $bundle = $file['node']->type->entity->label();
        // Validate if there are duplicated nodes.
        $this->importService->getNodesMatches($file['base_fields']['title']);
      }
      else {
        $op = 'Pending';
        $bundle = $file['bundle'];
      }

      if ($file['synced_delta'] == 1) {
        $source_changes++;
      }
      if ($file['synced_delta'] == -1) {
        $current_changes++;
      }

      $rows[] = [
        'filename' => $file['base_fields']['title'],
        'bundle' => $bundle,
        'synced' => $file['synced'],
        'uuid' => $file['uuid'],
        'operations' => $op,
      ];
    }

    if (!empty($current_changes)) {
      $this->importService->notifySyncFiles('current', $current_changes);
    }
    if (!empty($source_changes)) {
      $this->importService->notifySyncFiles('source', $source_changes);
    }

    $request = $this->requestStack->getCurrentRequest();
    $sort = TableSort::getSort($header, $request);
    $order_by = TableSort::getOrder($header, $request)['sql'];

    usort($rows, function (array $a, array $b) use ($sort, $order_by): int {
      $result = $a[$order_by] <=> $b[$order_by];
      return $sort === 'asc' ? $result : -$result;
    });

    $current_page = $this->pagerManager->createPager(count($rows), 50)->getCurrentPage();
    $chunks = array_chunk($rows, 50);
    $rows = $chunks[$current_page] ?? [];

    // Add the pager to the render array (optional, for paging through large tables).
    $build['content_sync_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No YML files found.'),
    ];

    // Add sorting functionality to the table header.
    $build['content_sync_table']['#tablesort'] = TRUE;

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Returns an array of elements formatted by a delta value.
   *
   * @return array
   *   Files array with delta.
   */
  private function formatSynced($files) {
    // Compare changed date.
    foreach ($files as $key => $file) {
      if (empty($file['node']) || $file['base_fields']['changed'] > $file['node']->getChangedTime()) {
        $files[$key]['synced'] = t('Changed in the source on @date', [
          '@date' => date('m/d/Y', $file['base_fields']['changed']),
        ]);
        $files[$key]['synced_delta'] = 1;
      }
      if (!empty($file['node']) && $file['base_fields']['changed'] < $file['node']->getChangedTime()) {
        $files[$key]['synced'] = t('Changed in the current environment on @date', [
          '@date' => date('m/d/Y', $file['node']->getChangedTime()),
        ]);
        $files[$key]['synced_delta'] = -1;
      }
      if (!empty($file['node']) && $file['base_fields']['changed'] == $file['node']->getChangedTime()) {
        $files[$key]['synced'] = t('Synced');
        $files[$key]['synced_delta'] = 0;
      }
    }
    return $files;
  }

}
