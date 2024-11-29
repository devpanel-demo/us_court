<?php

namespace Drupal\usc_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\simple_sitemap\Entity\EntityHelper;
use Drupal\simple_sitemap\Exception\SkipElementException;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Manager\EntityManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SimpleSitemapPluginBase;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGeneratorBase;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the menu link URL generator.
 *
 * @UrlGenerator(
 *   id = "entity_media",
 *   label = @Translation("Media link URL generator"),
 *   description = @Translation("Generates media URLs by overriding the 'entity' URL generator."),
 *   settings = {
 *     "overrides_entity_type" = "media",
 *   },
 * )
 */
class EntityMediaUrlGenerator extends EntityUrlGeneratorBase {

  /**
   * The UrlGenerator plugins manager.
   *
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager
   */
  protected $urlGeneratorManager;

  /**
   * Entities per queue item.
   *
   * @var int
   */
  protected $entitiesPerDataset;

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $entityMemoryCache;

  /**
   * The simple_sitemap.entity_manager service.
   *
   * @var \Drupal\simple_sitemap\Manager\EntityManager
   */
  protected $entitiesManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * EntityUrlGenerator constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   Simple XML Sitemap logger.
   * @param \Drupal\simple_sitemap\Settings $settings
   *   The simple_sitemap.settings service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\simple_sitemap\Entity\EntityHelper $entity_helper
   *   Helper class for working with entities.
   * @param \Drupal\simple_sitemap\Manager\EntityManager $entities_manager
   *   The simple_sitemap.entity_manager service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   *   The UrlGenerator plugins manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Logger $logger,
    Settings $settings,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityHelper $entity_helper,
    EntityManager $entities_manager,
    UrlGeneratorManager $url_generator_manager,
    MemoryCacheInterface $memory_cache,
    FileSystem $file_system,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $logger,
      $settings,
      $language_manager,
      $entity_type_manager,
      $entity_helper
    );
    $this->entitiesManager = $entities_manager;
    $this->urlGeneratorManager = $url_generator_manager;
    $this->entityMemoryCache = $memory_cache;
    $this->entitiesPerDataset = $this->settings->get('entities_per_queue_item', 50);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): SimpleSitemapPluginBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.logger'),
      $container->get('simple_sitemap.settings'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('simple_sitemap.entity_manager'),
      $container->get('plugin.manager.simple_sitemap.url_generator'),
      $container->get('entity.memory_cache'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDataSets(): array {
    $data_sets = [];
    $bundle_settings = $this->entitiesManager
      ->setSitemaps($this->sitemap)
      ->getAllBundleSettings();
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    $entityTypeStorage = $this->entityTypeManager->getStorage('media');
    $keys = $sitemap_entity_types['media']->getKeys();
    if (!empty($bundle_settings[$this->sitemap->id()]['media'])) {
      foreach ($bundle_settings[$this->sitemap->id()]['media'] as $bundle_name => $settings) {
        if (!empty($settings['index'])) {

          $query = $entityTypeStorage->getQuery();

          if (!empty($keys['id'])) {
            $query->sort($keys['id']);
          }
          if (!empty($keys['bundle'])) {
            $query->condition($keys['bundle'], $bundle_name);
          }

          if (!empty($keys['published'])) {
            $query->condition($keys['published'], 1);
          }
          elseif (!empty($keys['status'])) {
            $query->condition($keys['status'], 1);
          }
          $query->accessCheck(FALSE);
          $data_set = [
            'entity_type' => 'media',
            'id' => [],
          ];
          foreach ($query->execute() as $entity_id) {
            $data_set['id'][] = $entity_id;
            if (count($data_set['id']) >= $this->entitiesPerDataset) {
              $data_sets[] = $data_set;
              $data_set['id'] = [];
            }
          }
          // Add the last data set if there are some IDs gathered.
          if (!empty($data_set['id'])) {
            $data_sets[] = $data_set;
          }
        }
      }
    }

    return $data_sets;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   */
  protected function processDataSet($data_set): array {
    foreach ($this->entityTypeManager->getStorage($data_set['entity_type'])->loadMultiple((array) $data_set['id']) as $entity) {
      try {
        $paths[] = $this->processEntity($entity);
      }
      catch (SkipElementException $e) {
        continue;
      }
    }

    return $paths ?? [];
  }

  /**
   * Processes the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   *
   * @return array
   *   Processing result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\simple_sitemap\Exception\SkipElementException
   * @throws \InvalidArgumentException
   */
  protected function processEntity(EntityInterface $entity): array {
    $entity_settings = $this->entitiesManager
      ->setSitemaps($this->sitemap)
      ->getEntityInstanceSettings($entity->getEntityTypeId(), $entity->id());

    if (empty($entity_settings[$this->sitemap->id()]['index'])) {
      throw new SkipElementException();
    }

    $entity_settings = $entity_settings[$this->sitemap->id()];

    if (method_exists($entity, 'getSource')) {
      $file_id = $entity->getSource()->getSourceFieldValue($entity);
      if (!$file_id) {
        throw new SkipElementException();
      }
      $bg_file = File::load($file_id);
      if (!empty($bg_file)) {
        $file_uri = str_replace('public:///', 'public://', $bg_file->getFileUri());
        $url_object = Url::fromUri($file_uri)->setAbsolute();
        $file_path = $this->fileSystem->realpath($bg_file->getFileUri());
        if (!$file_path || !file_exists($file_path)) {
          throw new SkipElementException();
        }
      }
      else {
        throw new SkipElementException();
      }
    }
    else {
      throw new SkipElementException();
    }

    $processedEntity = [
      'url' => $this->replaceBaseUrlWithCustom($bg_file->createFileUrl(FALSE)),
      'lastmod' => method_exists($entity, 'getChangedTime')
        ? date('c', $entity->getChangedTime())
        : NULL,
      'priority' => $entity_settings['priority'] ?? NULL,
      'changefreq' => !empty($entity_settings['changefreq']) ? $entity_settings['changefreq'] : NULL,
      'images' => [],
      'meta' => [
        'path' => $bg_file->getFileUri(),
        'entity_info' => [
          'entity_type' => $entity->getEntityTypeId(),
          'id' => $entity->id(),
        ],
      ],
    ];
    return $processedEntity;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   */
  public function generate($data_set): array {
    $path_data_sets = $this->processDataSet($data_set);
    $url_variant_sets = [];
    foreach ($path_data_sets as $path_data) {
      if (isset($path_data['url'])) {
        $url_object = $path_data['url'];
        $url_variant_sets[] = [$path_data];
      }
    }
    if ($this->entityTypeManager->getDefinition($data_set['entity_type'])->isStaticallyCacheable()) {
      $this->entityMemoryCache->deleteAll();
    }
    return array_merge([], ...$url_variant_sets);
  }

}
