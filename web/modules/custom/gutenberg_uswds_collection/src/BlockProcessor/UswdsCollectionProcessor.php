<?php

namespace Drupal\gutenberg_uswds_collection\BlockProcessor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\gutenberg\BlockProcessor\GutenbergBlockProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes Gutenberg embeded content.
 */
class UswdsCollectionProcessor implements GutenbergBlockProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Gutenberg logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ReusableBlockProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Psr\Log\LoggerInterface $logger
   *   Gutenberg logger interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   * @throws \Exception
   *
   * @param-out \Drupal\Component\Render\MarkupInterface $block_content
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $block_attributes = $block['attrs'];
    if (empty($block_attributes['nodeIds'])) {
      $this->logger->error('Missing content ID.');
      return NULL;
    }

    $nids = $block_attributes['nodeIds'];
    $viewmode = $block_attributes['viewMode'] ?? 'calendar';
    $nodes = $this
      ->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($nids);
    $block_array = [];
    foreach ($nodes as $key => $node) {
      if ($node && $node->access('view')) {
        $render_content = $this
          ->entityTypeManager
          ->getViewBuilder('node')
          ->view($node, $viewmode);
        $render_content['#wrapper_attributes']['class'][] = 'usa-collection__item';
        $block_array[] = $render_content;
      }
    }

    $pre_render = $this->renderer->render($block_array);

    $render = [
      '#theme' => 'collection_container',
      '#content' => $pre_render,
      '#view_mode' => $viewmode,
    ];

    $render['#attached']['library'][] = 'sdc/uscgov--usa_collection';
    $block_content = $this->renderer->render($render);

    $bubbleable_metadata->addCacheableDependency(
      CacheableMetadata::createFromRenderArray($render)
    );

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported(array $block, $block_content = '') {
    return substr($block['blockName'] ?? '', 0, 16) === 'uswdscollection/';
  }

}
