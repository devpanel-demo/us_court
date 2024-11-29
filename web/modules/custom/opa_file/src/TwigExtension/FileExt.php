<?php

declare(strict_types=1);

namespace Drupal\opa_file\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
final class FileExt extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    $filters[] = new TwigFilter('get_ext', [$this, 'getExt']);
    return $filters;
  }

  /**
   * Get the extension of the file.
   *
   * @param string $filepath
   *   The url param to get.
   *
   * @return string
   *   The value of the url param.
   */
  public function getExt($filepath) {
    $ext = pathinfo($filepath, PATHINFO_EXTENSION);
    return $ext;
  }

}
