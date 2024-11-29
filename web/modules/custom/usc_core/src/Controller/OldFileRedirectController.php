<?php

declare(strict_types=1);

namespace Drupal\usc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for US Courts: Core routes.
 */
final class OldFileRedirectController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * Builds the response.
   *
   * @throws \Exception
   */
  public function __invoke($fid): Response {
    $migrated_files_query = $this->database->select('m_map_upgrade_d7_file_entity_document_public', 't');
    $migrated_files_query->fields('t');
    $migrated_files_query->condition('sourceid1', $fid);
    $migrated_files_query->range(0, 1);
    $migrated_files = $migrated_files_query->execute()->fetchAssoc();

    if (!$migrated_files) {
      throw new NotFoundHttpException();
    }

    // Getting the new media entity.
    $media_storage = $this->entityTypeManager()->getStorage('media');
    $media = $media_storage->load($migrated_files['destid1']);

    if (!$media instanceof MediaInterface || !$media->hasField('field_media_document')) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get('field_media_document')->entity;
    $file_uri = $file->getFileUri();
    $file_url = $this->fileUrlGenerator->generateAbsoluteString($file_uri);

    if (str_ends_with($file_uri, '.pdf')) {
      return new RedirectResponse($file_url);
    }
    else {
      $headers = [
        'Content-Type' => $file->getMimeType(),
        'Content-Disposition' => 'attachment;filename="' . $file->getFilename() . '"',
        'Content-Length' => $file->getSize(),
        'Content-Description' => ' File Transfer',
      ];

      if (!file_exists($file_uri)) {
        throw new NotFoundHttpException();
      }

      return new BinaryFileResponse($file_uri, 200, $headers, TRUE);
    }
  }

}
