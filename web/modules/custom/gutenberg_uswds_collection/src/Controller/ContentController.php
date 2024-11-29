<?php

namespace Drupal\gutenberg_uswds_collection\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling node entity queries to use as URLs.
 */
class ContentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Renderer service object.
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a new controller object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Private temporary storage factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service object.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RendererInterface $renderer,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Return a list of nodes containing a piece of search text.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $type
   *   The content type.
   * @param string $search
   *   The search string.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function search(Request $request, string $type = '', string $search = '') {
    $limit = (int) $request->query->get('per_page', 20);

    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();
    $query->condition('title', $search, 'CONTAINS');

    // Basic is going to be our mixed content type collection.
    if ($type != 'basic') {
      $query->condition('type', $type);
    }
    else {
      $query->condition('type', [
        'basic',
        'report',
        'landing_view',
        'news',
        'news_issue',
        'educational_activity'
      ], 'IN');
    }

    $node_ids = $query->condition('status', 1)
      ->sort('changed', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE)
      ->execute();

    $nodes = Node::loadMultiple($node_ids);
    $result = [];
    foreach ($nodes as $node) {
      if ($type == 'basic') {
        $title = $node->getTitle() . " (" . $node->bundle() . ") ";
      }
      else {
        $title = $node->getTitle();
      }
      $result[] = [
        'id' => $node->id(),
        'title' => $title,
      ];
    }

    return new JsonResponse($result);
  }

  /**
   * Returns JSON representing the loaded node.
   *
   * @param int $nid
   *   Node ID.
   * @param string $viewmode
   *   The view mode to render.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   * @throws \Exception
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadById($nid, $viewmode = 'default') {
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if ($node) {
      if ($node->access('view')) {
        $build = $view_builder->view($node, $viewmode);
        return new JsonResponse($this->renderer->render($build));
      }
      else {
        return new JsonResponse($this->t('You cannot view this content.'));
      }
    }
    return new JsonResponse($this->t('Unable to render node'));
  }

  /**
   * Returns JSON representing the loaded nodes.
   *
   * @param string $nids
   *   Node ID.
   * @param string $viewmode
   *   The view mode to render.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   * @throws \Exception
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadByIds($nids, $viewmode = 'default') {
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $nids_array = explode(',', $nids);
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids_array);
    if ($nodes) {
      $buildlist = [];
      foreach ($nids_array as $nid) {
        if (!empty($nodes[$nid])) {
          $node = $nodes[$nid];
          $node_build = $view_builder->view($node, $viewmode);
          $buildlist[] = $this->renderer->render($node_build);
        }
      }
      return new JsonResponse($buildlist);
    }
    return new JsonResponse($this->t('Unable to render node'));
  }

  /**
   * Returns JSON with latest nodes array.
   *
   * @param string $bundle
   *   Bundle ID.
   * @param int $items
   *   Number of items.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadByBundle($bundle, $items = 3) {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();
    $results = $query->condition('type', $bundle)
      ->condition('status', 1)
      ->range(0, $items)
      ->sort('changed', 'DESC')
      ->accessCheck(TRUE)
      ->execute();
    $results = array_values($results);
    if ($results) {
      return new JsonResponse($results);
    }
    return new JsonResponse($this->t('Unable to load nodes'));
  }

}
