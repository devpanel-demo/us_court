<?php

namespace Drupal\piksel\Plugin\media\Source;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\field\FieldConfigInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\piksel\PikselCacheWrapperInterface;
use Drupal\piksel\Plugin\PikselProviderManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\MimeTypes;

/**
 * Provides a media source plugin for piksel media resources.
 *
 * @MediaSource(
 *   id = "piksel",
 *   label = @Translation("Piksel"),
 *   description = @Translation("Use an piksel API to import local media items."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value",
 *   forms = {
 *     "media_library_add" = "Drupal\piksel\Form\PikselAddForm"
 *   }
 * )
 */
class Piksel extends MediaSourceBase {

  /**
   * The logger channel for media.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The token replacement service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The piksel media provider manager.
   *
   * @var \Drupal\piksel\Plugin\PikselProviderManager
   */
  protected $pikselProviderManager;

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The piksel media cache wrapper.
   *
   * @var \Drupal\piksel\PikselCacheWrapperInterface
   */
  protected $cacheWrapper;

  /**
   * Constructs a new Piksel instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for media.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement service.
   * @param \Drupal\piksel\Plugin\PikselProviderManager $piksel_provider_manager
   *   The piksel media provider manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   * @param \Drupal\piksel\PikselCacheWrapperInterface $cache_wrapper
   *   The piksel media cache wrapper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerInterface $logger, MessengerInterface $messenger, ClientInterface $http_client, FileSystemInterface $file_system, Token $token, PikselProviderManager $piksel_provider_manager, StreamWrapperManagerInterface $stream_wrapper_manager, PikselCacheWrapperInterface $cache_wrapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->token = $token;
    $this->pikselProviderManager = $piksel_provider_manager;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->cacheWrapper = $cache_wrapper;
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
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('logger.factory')->get('media'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('plugin.manager.piksel_provider'),
      $container->get('stream_wrapper_manager'),
      $container->get('piksel.cache.wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    return [
      'provider' => $this->t('Provider'),
      'title' => $this->t('Filename'),
      'url' => $this->t('File URL'),
      'description' => $this->t('Description'),
      'alt' => $this->t('Alt text'),
      'photographer' => $this->t('Photographer'),
      'photographer_url' => $this->t('Photographer URL'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\Core\File\Exception\NotRegularDirectoryException
   */
  public function getMetadata(MediaInterface $media, $name): ?string {
    $piksel_id = $this->getSourceFieldValue($media);
    if (empty($piksel_id)) {
      return NULL;
    }

    $provider_name = $this->getConfiguration()['provider'];
    $provider = $this->pikselProviderManager->createInstance($provider_name);
    $photo = $this->cacheWrapper->load($provider, $piksel_id);

    switch ($name) {
      case 'default_name':
        if ($title = $this->getMetadata($media, 'title')) {
          return $title;
        }
        if ($url = $this->getMetadata($media, 'url')) {
          return $url;
        }
        return parent::getMetadata($media, 'default_name');

      case 'thumbnail_uri':
        return $this->getLocalThumbnailUri($photo->getThumbnailUrl()) ?: parent::getMetadata($media, 'thumbnail_uri');

      case 'type':
        return $provider_name;

      case 'title':
        return $this->fileSystem->basename($photo->getUrl());

      case 'url':
        return $photo->getUrl();

      case 'description':
        return $photo->getDescription();

      case 'alt':
        return $photo->getAlt();

      default:
        break;
    }
    return NULL;
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param string $url
   *   The thumbnail URL.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\Core\File\Exception\NotRegularDirectoryException
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   */
  protected function getLocalThumbnailUri(string $url): ?string {
    $configuration = $this->getConfiguration();
    $directory = $configuration['thumbnails_directory'];
    $directory = $this->token->replace($directory);
    $directory = PlainTextOutput::renderFromHtml($directory);

    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    $hash = Crypt::hashBase64($url);
    $files = $this->fileSystem->scanDirectory($directory, "/^{$hash}\..*/");
    if (count($files) > 0) {
      return reset($files)->uri;
    }

    // The local thumbnail doesn't exist yet, so we need to download it.
    try {
      $response = $this->httpClient->request('GET', $url);
      if ($response->getStatusCode() === 200) {
        $local_thumbnail_uri = $directory . DIRECTORY_SEPARATOR . $hash . '.' . $this->getThumbnailFileExtensionFromUrl($url, $response);
        // @phpstan-ignore-next-line.
        $this->fileSystem->saveData((string) $response->getBody(), $local_thumbnail_uri, FileExists::Replace);
        return $local_thumbnail_uri;
      }
    }
    catch (TransferException $e) {
      $this->logger->warning($e->getMessage());
    }
    catch (FileException $e) {
      $this->logger->warning('Could not download remote thumbnail from {url}.', [
        'url' => $url,
      ]);
    }
    return NULL;
  }

  /**
   * Tries to determine the file extension of a thumbnail.
   *
   * @param string $thumbnail_url
   *   The remote URL of the thumbnail.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response for the downloaded thumbnail.
   *
   * @return string|null
   *   The file extension, or NULL if it could not be determined.
   */
  protected function getThumbnailFileExtensionFromUrl(string $thumbnail_url, ResponseInterface $response): ?string {
    // First, try to glean the extension from the URL path.
    $path = parse_url($thumbnail_url, PHP_URL_PATH);
    if ($path) {
      $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
      if ($extension) {
        return $extension;
      }
    }

    $content_type = $response->getHeader('Content-Type');
    // If there was no Content-Type header, there's nothing else we can do.
    if (empty($content_type)) {
      return NULL;
    }
    $extensions = MimeTypes::getDefault()->getExtensions(reset($content_type));
    if ($extensions) {
      return reset($extensions);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    $form['thumbnails_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnails location'),
      '#default_value' => $this->configuration['thumbnails_directory'],
      '#description' => $this->t('Thumbnails will be fetched from the provider for local usage. This is the URI of the directory where they will be placed.'),
      '#required' => TRUE,
    ];

    $providers = [];
    $definitions = $this->pikselProviderManager->getDefinitions();
    foreach ($definitions as $definition) {
      $providers[$definition['id']] = $definition['label'];
    }

    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#default_value' => $configuration['provider'],
      '#options' => $providers,
      '#description' => $this->t('Select an allowed piksel media provider for this media type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $thumbnails_directory = $form_state->getValue('thumbnails_directory');
    if (!$this->streamWrapperManager->isValidUri($thumbnails_directory)) {
      $form_state->setErrorByName('thumbnails_directory', $this->t('@path is not a valid path.', [
        '@path' => $thumbnails_directory,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'thumbnails_directory' => 'public://piksel_thumbnails/[date:custom:Y-m]',
      'provider' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display): void {
    parent::prepareFormDisplay($type, $display);
    $source_field = $this->getSourceFieldDefinition($type)->getName();

    $display->setComponent($source_field, [
      'type' => 'string_textfield',
      'weight' => $display->getComponent($source_field)['weight'],
    ]);
    $display->removeComponent('name');
  }

  /**
   * Returns the piksel media provider name.
   *
   * @return string
   *   The configured provider name.
   */
  public function getProvider(): string {
    $configuration = $this->getConfiguration();
    return $configuration['provider'] ?: $this->getPluginDefinition()['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type): FieldConfigInterface {
    $plugin_definition = $this->getPluginDefinition();

    $label = (string) $this->t('@type ID', [
      '@type' => $plugin_definition['label'],
    ]);
    return parent::createSourceField($type)->set('label', $label);
  }

}
