<?php

namespace Drupal\usc_core\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Report Links Extra field.
 *
 * The class was ported from the D7 "US Courts Menus" uscourts_menus module
 * covering "reports right navigation block" functionality adn mostly keeping
 * the original code comments.
 *
 * @ExtraFieldDisplay(
 *   id = "report_links",
 *   label = @Translation("Report links"),
 *   bundles = {
 *     "node.report",
 *   }
 * )
 */
class ReportLinks extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new ReportLinks object.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_link_tree, MenuLinkManagerInterface $menu_link_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, MenuActiveTrailInterface $menu_active_trail, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->menuActiveTrail = $menu_active_trail;
    $this->aliasManager = $alias_manager;
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
      $container->get('plugin.manager.menu.link'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('menu.active_trail'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return 'report_links';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Report links');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Provides a list of Report links.');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function view(ContentEntityInterface $entity) {
    $render_array = [
      '#markup' => '',
    ];

    if ($entity instanceof NodeInterface) {
      // Are we a parent node?
      if ($this->isReportParentNode($entity)) {
        $render_array = $this->reportsFormatter($entity);
      }
      // Are we a child node?
      elseif ($entity = $this->getReportParentNode($entity)) {
        $render_array = $this->reportsFormatter($entity);
      }
      // Are we in the menu system?
      else {
        $render_array = $this->reportsFormatterFromMenu();
      }
    }

    return $render_array;
  }

  /**
   * Checks if the current node a report parent.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return bool
   *   TRUE if the node has children, FALSE otherwise.
   *
   * @throws \Exception
   */
  private function isReportParentNode($node) {
    $node = $this->getNodeLatestRevision($node);
    return !$node->get('field_report_children')->isEmpty();
  }

  /**
   * Checks if the current node a report parent.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\node\NodeInterface|bool
   *   A parent node if found, FALSE otherwise.
   *
   * @throws \Exception
   */
  private function getReportParentNode($node) {
    // Get the entity query service for nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    // Add conditions to the query.
    $query->condition('type', 'report')
      ->condition('field_report_children.target_id', $node->id());
    $query->accessCheck(TRUE);

    $parents = [];

    if ($result = $query->execute()) {
      // Now, cycle through those and found that reference our current page.
      foreach ($result as $parent_node_id) {
        // Load the current revision of the parent node.
        /** @var \Drupal\node\Entity\Node $parent_node */
        $parent_node = $this->entityTypeManager->getStorage('node')->load($parent_node_id);
        $children_ids = $parent_node->field_report_children->getValue();
        foreach ($children_ids as $child) {
          if ($node->id() === $child['target_id']) {
            $parents[$parent_node_id] = $parent_node;
            break;
          }
        }
      }
      // If we have more than one parent node left, warn the user.
      if (count($parents) > 1) {
        foreach ($parents as $parent_node) {
          $this->setErrorIfMultipleParents($parent_node);
        }
      }
    }

    return empty($parents) ? FALSE : array_pop($parents);
  }

  /**
   * Sets an error message if the node is a child for more than one report.
   *
   * @param \Drupal\node\NodeInterface $parent_node
   *   The parent node entity.
   */
  private function setErrorIfMultipleParents(NodeInterface $parent_node) {
    // Create a URL object for the node.
    $url = Url::fromRoute('entity.node.canonical', ['node' => $parent_node->id()]);

    // Create a Link object.
    $link = Link::fromTextAndUrl($parent_node->label(), $url)->toString();

    // Set the error message with the link.
    $this->messenger->addError($this->t('This page is a child for more than one report:') . ' ' . $link);
  }

  /**
   * A builder for report links.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return array
   *   A render array with report links.
   *
   * @throws \Exception
   */
  private function reportsFormatter($node) {
    $render = [];

    $node = $this->getNodeLatestRevision($node);

    // Load the children.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface */
    $field_report_children = $node->get('field_report_children');
    $children = $field_report_children->referencedEntities();

    // Get the title from the node.
    $title = $node->field_sidebar_title->value ?? $node->label();
    $render['title'] = [
      '#markup' => $title
    ];

    // Merge together the parent node and the children in order to output links.
    $links = array_merge([$node], $children);
    $link_items = [];
    /** @var \Drupal\node\NodeInterface $link */
    foreach ($links as $link) {
      $link = $this->getNodeLatestRevision($link);
      if ($link->access('view', $this->currentUser)) {
        $link_title = $link->field_sidebar_title->value ?? $link->label();
        $url = Url::fromRoute('entity.node.canonical', ['node' => $link->id()]);
        $link_renderable = Link::fromTextAndUrl($link_title, $url)->toRenderable();
        $link_items[] = $link_renderable;
      }
    }

    $list_items = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#attributes' => ['class' => ['in-this-section__list']],
      '#items' => $link_items,
    ];

    $attributes = new Attribute(['class' => 'report-links']);

    $render = [
      '#type' => 'component',
      '#component' => 'uscgov:in-this-section',
      '#slots' => [
        'list_items' => $list_items,
        'title' => $title,
      ],
      '#props' => [
        'attributes' => $attributes,
      ]
    ];

    return $render;
  }

  /**
   * Gets the latest revision of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\node\NodeInterface
   *   The latest revision of the node if the user has the 'view revisions' permission,
   *   otherwise the original node.
   *
   * @throws \Exception
   */
  private function getNodeLatestRevision($node) {
    if (!$this->currentUser->hasPermission('view revisions')) {
      return $node;
    }
    /** @var \Drupal\Core\Entity\ContentEntityStorageBase  */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $latest_revision_id = $nodeStorage->getLatestRevisionId($node->id());

    // Load the latest revision.
    $node = $nodeStorage->loadRevision($latest_revision_id);

    /** @var \Drupal\node\Entity\Node $node */
    return $node;
  }

  /**
   * A builder for report links.
   *
   * This is the old right nav block formatter, based on the menu system. We
   * only output it (or attempt to) if the report node we're rendering does
   * not have children nodes or a parent node by means of the report children
   * field.
   *
   * @TODO The testing is blocked till the Statistics & Reports item is not
   * added to the Main menu. The follow-up ticket
   * https://agileana.atlassian.net/browse/USCGOV-907
   *
   * @return array
   *   A render array with report links.
   *
   * @throws \Exception
   */
  private function reportsFormatterFromMenu() {
    $render = [
      '#markup' => '',
    ];

    // Get the active trail.
    $active_trail = $this->menuActiveTrail->getActiveTrailIds('main');

    // Ensure we have at least 3 links: home, top-level parent (pa) with a child (leaf).
    if (count($active_trail) >= 3) {
      // Remove the associative keys and reindex the array.
      $active_trail = array_values($active_trail);

      // Extract UUIDs from the active trail.
      $parent_uuid = str_replace('menu_link_content:', '', $active_trail[1]);

      $parent_node = $this->getNodeFromMenuLinkContentUuid($parent_uuid);

      if (!$parent_node || $parent_node->bundle() !== 'report') {
        $parent_uuid = str_replace('menu_link_content:', '', $active_trail[0]);
        $parent_node = $this->getNodeFromMenuLinkContentUuid($parent_uuid);
      }

      // Verify the parent node is of type 'report'.
      if ($parent_node && $parent_node->bundle() === 'report') {
        $title = $parent_node->label();

        // Load the menu link content entities.
        $parent_link = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $parent_uuid]);
        $parent_link = reset($parent_link);

        // Add the parent link.
        $parent_url = Url::fromRoute('entity.node.canonical', ['node' => $parent_node->id()]);
        $link_items[] = Link::fromTextAndUrl($parent_node->label(), $parent_url)->toRenderable();

        // Build menu parameters.
        $parameters = new MenuTreeParameters();
        $parameters->setRoot($parent_link->getPluginId())
          ->onlyEnabledLinks()
          ->setMinDepth(1)
          ->setMaxDepth(1);

        // Load the menu tree.
        $tree = $this->menuLinkTree->load($parent_link->getMenuName(), $parameters);
        $manipulators = [
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];
        $tree = $this->menuLinkTree->transform($tree, $manipulators);

        // Render children items.
        if (!empty($tree)) {
          foreach ($tree as $element) {
            $link = $element->link;
            $link_items[] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
          }
        }

        $list_items = [
          '#theme' => 'item_list',
          '#list_type' => 'ol',
          '#attributes' => ['class' => ['in-this-section__list']],
          '#items' => $link_items,
        ];

        $attributes = new Attribute(['class' => 'report-links']);

        $render = [
          '#type' => 'component',
          '#component' => 'uscgov:in-this-section',
          '#slots' => [
            'list_items' => $list_items,
            'title' => $title,
          ],
          '#props' => [
            'attributes' => $attributes,
          ]
        ];
      }
    }

    return $render;
  }

  /**
   * Retrieves a node object based on the menu link content UUID.
   *
   * @param string $menu_link_content_uuid
   *   The UUID of the menu link content entity.
   *
   * @return \Drupal\node\NodeInterface|false
   *   The node object if found, otherwise false.
   *
   * @throws \Exception
   */
  private function getNodeFromMenuLinkContentUuid($menu_link_content_uuid) {
    // Load the menu link content entity using the provided UUID.
    $menu_link_content_entities = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $menu_link_content_uuid]);
    /** @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent */
    $menu_link_content_entity = reset($menu_link_content_entities);

    if ($menu_link_content_entity) {
      // Get the URL object from the menu link content entity.
      $url_object = $menu_link_content_entity->getUrlObject();
      try {
        // Try to get the URI. This will fail if the URL has a Drupal route.
        $uri = $url_object->getUri();
      }
      catch (\UnexpectedValueException $e) {
        // If an exception is thrown, get the internal path instead.
        $uri = $url_object->getInternalPath();
      }

      // Function to extract node ID from URI.
      $extract_nid = function ($uri) {
        // Check if the URI is in the form 'node/<nid>'.
        if (preg_match('/^node\/(\d+)$/', $uri, $matches)) {
          return $matches[1];
        }
        else {
          // Get the path by alias in case the URI is an alias.
          $path = $this->aliasManager->getPathByAlias(parse_url('/' . ltrim($uri, '/'), PHP_URL_PATH));
          if (preg_match('/^\/node\/(\d+)$/', $path, $matches)) {
            return $matches[1];
          }
          else {
            return FALSE;
          }
        }
      };

      // Extract node ID from the URI.
      $nid = $extract_nid($uri);

      if ($nid) {
        // Load the node using the node ID.
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        /** @var \Drupal\node\NodeInterface $node */
        return $node;
      }
    }

    return FALSE;
  }

}
