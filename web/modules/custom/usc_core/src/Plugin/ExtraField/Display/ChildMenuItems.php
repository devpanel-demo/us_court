<?php

namespace Drupal\usc_core\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Child Menu Items Extra field.
 *
 * @ExtraFieldDisplay(
 *   id = "child_menu_items",
 *   label = @Translation("Child Menu Items"),
 *   bundles = {
 *     "node.*",
 *   }
 * )
 */
class ChildMenuItems extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;


  /**
   * The menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Constructs a new ChildMenuItems object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_link_tree, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return 'child_menu_items';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Child Menu Items');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Provides a list of child menu items.');
  }

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $render_array = [
      '#markup' => '',
    ];

    if ($entity->getEntityTypeId() === 'node') {
      // Load the preferred menu link for this node.
      $menu_links = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $entity->id()]);

      if (!empty($menu_links)) {
        $menu_link = reset($menu_links);

        // Use MenuTreeParameters to set the root and depth.
        $parameters = new MenuTreeParameters();
        $parameters->setRoot($menu_link->getPluginId());
        $parameters->onlyEnabledLinks();
        // Set min_depth and max_depth to fetch only direct children.
        $parameters->setMinDepth(1);
        $parameters->setMaxDepth(1);

        $tree = $this->menuLinkTree->load($menu_link->getMenuName(), $parameters);

        if (!empty($tree)) {
          // Extract the weights and store them in a separate array.
          $weights = [];
          foreach ($tree as $key => $element) {
            $weights[$key] = $element->link->getWeight();
          }

          // Sort the tree by weights.
          array_multisort($weights, SORT_ASC, $tree);

          // Build the tree array.
          $tree = $this->menuLinkTree->build($tree);
          $tree['#menu_name'] = 'landing';
          $tree['#theme'] = 'menu__landing';

          $render_array = $tree;
        }
      }
    }

    return $render_array;
  }

}
