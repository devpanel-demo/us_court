<?php

namespace Drupal\usc_core\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Drush commands.
 */
final class CoreCommands extends DrushCommands {

  /**
   * Constructs a CoreCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Update all pixel thumbnails.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException   *
   */
  #[CLI\Command(name: 'usc_core:update-pixel-thumbnails', aliases: ['usc_upt'])]
  #[CLI\Usage(name: 'usc_core:update-pixel-thumbnails', description: 'The command updates all pixel thumbnails.')]
  public function updateThumbnails() {
    $media_storage = $this->entityTypeManager->getStorage('media');
    $query = $media_storage->getQuery()
      ->condition('bundle', 'piksel_program')
      ->accessCheck(FALSE);
    $mids = $query->execute();

    if (empty($mids)) {
      $this->logger->notice(dt('No media entities found with the bundle "piksel_program".'));
      return;
    }

    $total = count($mids);
    $progress = $this->io()->createProgressBar($total);
    $progress->start();

    // Process each media entity and update the progress bar.
    $this->logger->notice(dt('Starting to process pixel media ...'));
    foreach ($mids as $mid) {
      $this->processMedia((int) $mid);
      $progress->advance();
    }

    $progress->finish();
    $this->logger->notice(dt('Processed all media entities.'));
  }

  /**
   * Update the thumbnail for a media entity.
   *
   * @param int $mid
   *   The media entity ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function processMedia(int $mid): void {
    $media_storage = $this->entityTypeManager->getStorage('media');
    /** @var \Drupal\media\Entity\Media $media */
    $media = $media_storage->load($mid);

    if ($media) {
      try {
        $media->updateQueuedThumbnail();
        $media->save();
      }
      catch (\Exception $e) {
        $this->logger->error(dt('Failed to update media entity with ID: @id. Error: @error', [
          '@id' => $media->id(),
          '@error' => $e->getMessage(),
        ]));
      }
    }
    else {
      $this->logger->error(dt('Media entity with ID: @id not found', ['@id' => $mid]));
    }
  }

}
