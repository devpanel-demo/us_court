<?php

namespace Drupal\usc_vacancies;

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
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;

/**
 * Service to import vacancies.
 */
class VacancyImportService implements VacancyImportServiceInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Plugin manager for migration plugins.
   */
  protected MigrationPluginManagerInterface $migrationPluginManager;

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
   * Constructs an admin form.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
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
   */
  public function __construct(MessengerInterface $messenger, MigrationPluginManagerInterface $migration_plugin_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, State $state, KeyValueFactoryInterface $key_value_factory, Time $time, FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    $this->messenger = $messenger;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('usc_vacancies.settings');
    $this->state = $state;
    $this->keyValue = $key_value_factory->get('migrate_last_imported');
    $this->time = $time;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Helper function to import a judicial archive.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \InvalidArgumentException
   * @throws \ValueError
   */
  public function importDataJudicial($year_month = NULL) {
    if (is_null($year_month)) {
      $year_month = date('Y_m');
    }

    $base_dir = $this->config->get('usc_dir_judicial');
    $archive_dir = $this->config->get('usc_dir_judicial_archive');
    $full_path = $base_dir . '/' . $archive_dir . '/' . $year_month;

    $migration_ids = [];
    if (is_dir($full_path)) {
      $archive_files = scandir($full_path, 1);

      foreach ($archive_files as $archive_file) {
        if ($archive_file != '.' && $archive_file != '..') {
          $migration_ids[$archive_file] = $this->getMigrationForFile($archive_file);
        }
      }
    }
    else {
      throw new PluginException("Directory not found $full_path");
    }

    foreach ($migration_ids as $filename => $migration_id) {

      /** @var \Drupal\migrate\Plugin\Migration $migration */
      $migration = $this->migrationPluginManager->createInstance($migration_id);
      if ($migration instanceof MigrationInterface) {
        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_id', $year_month);
        // phpcs:enable

        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_path', $full_path);
        // phpcs:enable

        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_filename', $filename);
        // phpcs:enable

        $source = $migration->getSourcePlugin();

        $row = new Row();
        if (str_ends_with($filename, '.pdf')) {
          $absolute_path = $this->fileSystem->realpath($full_path . '/' . $filename);
          /* @phpstan-ignore-next-line */
          $fid = $this->copyFile($absolute_path, $year_month, $filename);
          $migration->set('field_pdf_document', $fid);
        }

        $migration->getIdMap()->prepareUpdate();

        $this->moduleHandler->invokeAll('migrate_prepare_row', [$row, $source, $migration]);
        $executable = new MigrateExecutable($migration, new MigrateMessage());

        try {
          // Run the migration.
          $executable->import();
          $this->keyValue->set($migration_id, (int) $this->time->getRequestTime() * 1000);
        }
        catch (\Exception $e) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
          $this->logger->get('usc_vacancies')->error($e->getMessage());
          return FALSE;
        }
      }
    }

    return TRUE;

  }

  /**
   * Helper function to import a judicial archive.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \InvalidArgumentException
   * @throws \ValueError
   */
  public function importCurrentJudicial() {

    $base_dir = $this->config->get('usc_dir_judicial');
    $full_path = $base_dir . '/';
    $year_month = date('Y_m');

    $migration_ids = [];
    if (is_dir($full_path)) {
      $vacancies_files = scandir($full_path, 1);

      foreach ($vacancies_files as $vacancy_file) {
        if ($vacancy_file != '.' && $vacancy_file != '..' && !is_dir($full_path . $vacancy_file)) {
          $migration_ids[$vacancy_file] = $this->getMigrationForFile($vacancy_file);
          $time = filemtime($full_path . $vacancy_file);
          $year_month = date('Y_m', $time);
        }
      }
    }
    else {
      throw new PluginException("Directory not found $full_path");
    }

    foreach ($migration_ids as $filename => $migration_id) {

      /** @var \Drupal\migrate\Plugin\Migration $migration */
      $migration = $this->migrationPluginManager->createInstance($migration_id);
      if ($migration instanceof MigrationInterface) {
        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_id', $year_month);
        // phpcs:enable

        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_path', $full_path);
        // phpcs:enable

        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_filename', $filename);
        // phpcs:enable

        $source = $migration->getSourcePlugin();

        $row = new Row();
        if (str_ends_with($filename, '.pdf')) {
          $absolute_path = $this->fileSystem->realpath($full_path . '/' . $filename);
          /* @phpstan-ignore-next-line */
          $fid = $this->copyFile($absolute_path, $year_month, $filename);
          $migration->set('field_pdf_document', $fid);
        }

        $migration->getIdMap()->prepareUpdate();

        $this->moduleHandler->invokeAll('migrate_prepare_row', [$row, $source, $migration]);
        $executable = new MigrateExecutable($migration, new MigrateMessage());

        try {
          // Run the migration.
          $executable->import();
          $this->keyValue->set($migration_id, (int) $this->time->getRequestTime() * 1000);
        }
        catch (\Exception $e) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
          $this->logger->get('usc_vacancies')->error($e->getMessage());
          return FALSE;
        }
      }
    }

    return TRUE;

  }

  /**
   * Copy a file to Drupal's public file system and create a managed file entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\File\Exception\FileException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \ValueError
   */
  public function copyFile($absolute_path, $year_month, $filename) {

    $directory = 'public://vacancies_documents/' . $year_month;
    // Ensure that the 'public://pdfs' directory exists.
    $this->ensureDirectoryExists($directory);

    // Define the destination path in the public file system.
    $destination = $directory . '/' . $year_month . $filename;

    // Check if the file already exists in the public:// directory.
    if ($existing_file = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $destination])) {
      // File already exists in public://, return the existing file ID (fid).
      $existing_file = reset($existing_file);
      return $existing_file->id();
    }

    // If the file does not exist, check if it exists in the original location.
    if (file_exists($absolute_path)) {
      // Copy the file to the public file system.
      $this->fileSystem->copy($absolute_path, $destination, FileExists::Replace);

      // Create a File entity for the copied file.
      $file = File::create([
        'uri' => $destination,
        'status' => 1,
      ]);
      $file->save();

      // Return the File entity ID (fid).
      return $file->id();
    }
    else {
      $this->logger->get('usc_vacancies')->error('File not found: ' . $absolute_path);
      return NULL;
    }
  }

  /**
   * Ensure that the destination directory exists and is writable.
   */
  public function ensureDirectoryExists($directory = 'public://pdfs') {
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
  }

  /**
   * Helper function to import a judicial archive.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \InvalidArgumentException
   * @throws \ValueError
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importDataJudicialArchive() {

    $base_dir = $this->config->get('usc_dir_judicial');
    $archive_dir = $this->config->get('usc_dir_judicial_archive');
    $full_path = $base_dir . '/' . $archive_dir;

    print_r("\r\nImporting from folder $full_path");

    // Get the archive dirs.
    foreach (scandir($full_path) as $directory_file) {
      $original_file_path = "{$full_path}/{$directory_file}";
      // Ensure only files from assets folder are imported.
      $info = pathinfo($original_file_path);
      if (is_dir($original_file_path) && $info['filename'] != '.' && $info['filename'] != '') {
        // Iterate nested folder.
        print_r("\r\n" . "Importing archive " . $info['filename']);
        $this->importDataJudicial($info['filename']);
      }
    }

    return TRUE;

  }

  /**
   * Builds an array tree of archive directories organized by year.
   *
   * YYYY_MM is the format of the archive directories.
   * Example tree:  [
   *                  '2015' => ['2015_08' => 08', '2015_10' => '10'],
   *                  '2014' => ['2014_05' => '05'],
   *                  '2013' => ['2013_07' => '07', '2013_11' => '11']
   *                ].
   */
  public function scanJudicialArchiveDirs() {
    $dirs = [];
    $base_dir = $this->config->get('usc_dir_judicial');
    $archive_dir = $this->config->get('usc_dir_judicial_archive');
    $full_archive_dir = $base_dir . '/' . $archive_dir;

    /*
     * Get all files and directories under the full archive path.
     * We will store it in State.
     */
    $scan_results = scandir($full_archive_dir, 1);

    if ($scan_results !== FALSE) {
      // Filter out ".", "..", files and directories not matching YYYY_MM naming formats.
      $scan_results = array_filter($scan_results, function ($element) use ($full_archive_dir) {
        return preg_match('/^\d\d\d\d_\d\d$/', $element) && is_dir($full_archive_dir . '/' . $element);
      });

      // Organize directories by year.
      foreach ($scan_results as $dir) {
        [$dir_year, $dir_month] = explode('_', $dir);
        if (!isset($dirs[$dir_year])) {
          $dirs[$dir_year] = [];
        }
        if (!isset($dirs[$dir_year][$dir])) {
          $dirs[$dir_year][$dir] = $dir_month;
        }
      }
    }

    // Return whole tree.
    return $dirs;

  }

  /**
   * Helper function to get the key of the judicial archives.
   */
  public function getCacheKey() {
    $cache_prefix = $this->config->get('usc_prefix_cache');
    $judicial_prefix = $this->config->get('usc_prefix_judicial');

    $cache_key = $cache_prefix . $judicial_prefix . 'archive';
    return $cache_key;
  }

  /**
   * Helper function to clear the cache for Judicial Archives.
   */
  public function clearJudicialArchiveCache() {
    $cache_key = $this->getCacheKey();
    $this->state->delete($cache_key);
  }

  /**
   * Helper function to judicial archive data.
   */
  public function getJudicialArchiveDirs($year = NULL) {
    $dirs = &drupal_static(__FUNCTION__);

    if (!isset($dirs)) {
      $cache_key = $this->getCacheKey();
      $dirs = $this->state->get($cache_key, []);

      if (!empty($dirs)) {
        return $dirs;
      }

      $dirs = $this->scanJudicialArchiveDirs();
      $this->state->set($cache_key, $dirs);

      if (!is_null($year)) {
        return $dirs[$year] ?? [];
      }
    }
    return $dirs;

  }

  /**
   * Helper function to get the appropriate migration for the given filename.
   */
  public function getMigrationForFile($filename) {
    $filename_arr = explode('.', $filename);
    $root_name = $filename_arr[0];
    $ext = $filename_arr[1];

    $migration_ids = [
      'xml' => [
        'jdarvac2' => 'usc_vacancy_judicial_confirmation_feed',
        'jdarvac1_current' => 'usc_vacancy_judicial_current_feed',
        'jdarevac' => 'usc_vacancy_judicial_emergency_feed',
        'jdarvac1_future' => 'usc_vacancy_judicial_future_feed',
        'jdarjcnt' => 'usc_vacancy_judicial_summary_feed',
      ],
      'html' => [
        'jdarvac2' => 'usc_vacancy_judicial_confirmation_html_feed',
        'jdarvac1_current' => 'usc_vacancy_judicial_current_html_feed',
        'jdarevac' => 'usc_vacancy_judicial_emergency_html_feed',
        'jdarvac1_future' => 'usc_vacancy_judicial_future_html_feed',
        'jdarjcnt' => 'usc_vacancy_judicial_summary_html_feed',
      ],
      'pdf' => [
        'jdarvac2' => 'usc_vacancy_judicial_confirmation_pdf_feed',
        'jdarvac1_current' => 'usc_vacancy_judicial_current_pdf_feed',
        'jdarevac' => 'usc_vacancy_judicial_emergency_pdf_feed',
        'jdarvac1_future' => 'usc_vacancy_judicial_future_pdf_feed',
        'jdarjcnt' => 'usc_vacancy_judicial_summary_pdf_feed',
      ],
    ];

    return $migration_ids[$ext][$root_name];
  }

  /**
   * Helper function to import a job data.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \InvalidArgumentException
   */
  public function importDataJob() {
    $job_base_path = $this->config->get('usc_dir_job');
    $job_file_name = $this->config->get('usc_file_job_vacancies');
    $migration_ids = [
      'usc_job_notice_feed',
    ];
    foreach ($migration_ids as $migration_id) {
      /** @var \Drupal\migrate\Plugin\Migration $migration */
      $migration = $this->migrationPluginManager->createInstance($migration_id);
      if ($migration instanceof MigrationInterface) {
        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_path', $job_base_path);
        // phpcs:enable

        // phpcs:disable
        /* @phpstan-ignore-next-line */
        $migration->set('archive_filename', $job_file_name);
        // phpcs:enable

        $source = $migration->getSourcePlugin();
        $row = new Row();

        $migration->getIdMap()->prepareUpdate();

        $this->moduleHandler->invokeAll('migrate_prepare_row', [$row, $source, $migration]);
        $executable = new MigrateExecutable($migration, new MigrateMessage());

        try {
          // Run the migration.
          $executable->import();
        }
        catch (\Exception $e) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
          return FALSE;
        }
      }
    }

    return TRUE;

  }

}
