<?php

namespace Drupal\usc_content_sync;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\node\NodeInterface;
use Drupal\single_content_sync\ContentExporterInterface;
use Drupal\single_content_sync\ContentSyncHelperInterface;

/**
 * Service to sync content.
 */
class NodeSyncService implements EntitySyncServiceInterface {

  use StringTranslationTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The drupal time.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content sync helper.
   *
   * @var \Drupal\single_content_sync\ContentSyncHelperInterface
   */
  protected $contentSyncHelper;

  /**
   * The content exporter.
   *
   * @var \Drupal\single_content_sync\ContentExporterInterface
   */
  protected $contentExporter;

  /**
   * The sync folder.
   *
   * @var string
   */
  protected $syncFolder;

  /**
   * The file system.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Constructs an admin form.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\State $state
   *   The drupal state service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value store factory.
   * @param \Drupal\Component\Datetime\Time $time
   *   The drupal time.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The drupal file system.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\single_content_sync\ContentSyncHelperInterface $content_sync_helper
   *   The content sync helper.
   * @param \Drupal\single_content_sync\ContentExporterInterface $content_exporter
   *   The content exporter.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository.
   */
  public function __construct(MessengerInterface $messenger, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, State $state, KeyValueFactoryInterface $key_value_factory, Time $time, FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger, EntityTypeManagerInterface $entity_type_manager, ContentSyncHelperInterface $content_sync_helper, ContentExporterInterface $content_exporter, FileRepositoryInterface $file_repository) {
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('usc_content_sync.settings');
    $this->state = $state;
    $this->keyValue = $key_value_factory->get('migrate_last_imported');
    $this->time = $time;
    $this->fileSystem = $file_system;
    // phpcs:ignore
    // @phpstan-ignore-next-line
    $this->logger = $logger->get('usc_content_sync');
    $this->entityTypeManager = $entity_type_manager;
    $this->contentSyncHelper = $content_sync_helper;
    $this->contentExporter = $content_exporter;
    $this->fileRepository = $file_repository;

    $this->syncFolder = $this->config->get('usc_cs_import_folder') . '/' . $this->config->get('usc_cs_content_directory');
    if (!str_starts_with($this->syncFolder, '/')) {
      $this->syncFolder = DRUPAL_ROOT . '/' . $this->syncFolder;
    }
  }

  /**
   * Helper function to get the YML files.
   *
   * @throws \Exception
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRawFiles() {

    $files = $sync_files = [];

    // Get all YML files in the directory.
    $files = glob($this->syncFolder . '/*.yml');

    foreach ($files as $key => $file) {
      $file_content = file_get_contents($file);
      if (!$file_content) {
        throw new \Exception('The requested file could not be downloaded.');
      }
      else {
        $sync_files[] = $this->contentSyncHelper->validateYamlFileContent($file_content);
      }
    }
    return $sync_files;
  }

  /**
   * Helper function to get the YML files and associated nodes.
   *
   * @throws \Exception
   *
   * @return array
   *   files and nodes in a single array.
   */
  public function getFilesAndNodes() {

    $raw_files = $this->getRawFiles();
    $formatted_files = $uuids = [];
    // Load entities.
    foreach ($raw_files as $key => $raw_file) {
      $uuids[] = $raw_file['uuid'];
      $formatted_files[$raw_file['uuid']] = $raw_file;
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $uuids]);

    foreach ($nodes as $key => $node) {
      if (!empty($formatted_files[$node->uuid()])) {
        $formatted_files[$node->uuid()]['node'] = $node;
      }
    }

    return $formatted_files;
  }

  /**
   * Export the YML files to the configured folder.
   *
   * @throws \Exception
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exportSyncFolder() {

    $raw_files = $this->getRawFiles();
    $uuids = [];
    // Load entities.
    foreach ($raw_files as $key => $raw_file) {
      $uuids[] = $raw_file['uuid'];
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $uuids]);

    foreach ($nodes as $key => $node) {
      $this->exportNodeYml($node);
    }
  }

  /**
   * Export a single node to the configured folder.
   */
  public function exportNodeYml(NodeInterface $node) {

    $output = $this->contentExporter->doExportToYml($node, FALSE);
    $directory = $this->syncFolder;
    $file_name = $this->contentSyncHelper->generateContentFileName($node);
    // phpcs:ignore
    // @phpstan-ignore-next-line
    $this->logger->notice('Exported Node ' . $node->getTitle() . ' ID ' . $node->id() . ' to the default content.');
    $path = "{$directory}/{$file_name}.yml";
    $this->contentSyncHelper->prepareFilesDirectory($directory);
    if (is_writable(dirname($path))) {
      file_put_contents($path, $output);
    }
  }

  /**
   * Delete the reference YML file.
   */
  public function deleteFile(NodeInterface $node) {

    $output = $this->contentExporter->doExportToYml($node, FALSE);
    $directory = $this->syncFolder;
    $file_name = $this->contentSyncHelper->generateContentFileName($node);
    $file_path = "{$directory}/{$file_name}.yml";

    if (file_exists($file_path)) {
      if (unlink($file_path)) {
        $this->messenger->addStatus($this->t('The node %file has been excluded from the syncing process.', ['%file' => $node->getTitle()]));
      }
      else {
        $this->messenger->addError($this->t('Unable to delete the file %file.', ['%file' => $file_path]));
      }
    }
    else {
      $this->messenger->addError($this->t('The file %file does not exist.', ['%file' => $file_path]));
    }
  }

  /**
   * Notify content to sync.
   */
  public function notifySyncFiles($type, $number) {
    $entity = 'Nodes';
    if ($number == 1) {
      $entity = 'Node';
    }
    if ($type == 'source') {
      $this->messenger->addWarning($this->t("$number $entity to be imported"));
    }
    if ($type == 'current') {
      $this->messenger->addWarning($this->t("$number $entity changed in the current environment"));
    }
  }

  /**
   * Validate the if there is a file for the node in the active directory.
   */
  public function isSyncing(NodeInterface $node) {

    $directory = $this->syncFolder;
    $file_name = $this->contentSyncHelper->generateContentFileName($node);
    $file_path = "{$directory}/{$file_name}.yml";

    if (file_exists($file_path)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate the if there are additional nodes with the same name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNodesMatches(string $title) {

    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['title' => $title]);

    if (count($nodes) > 1) {
      $this->messenger->addWarning($this->t("There are multiple nodes with the name: $title, please validate if there are duplicated nodes in your database."));
    }
  }

}
