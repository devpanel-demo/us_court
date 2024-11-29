<?php

declare(strict_types=1);

namespace Drupal\usc_court_finder\TwigExtension;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
final class UscCourtFinderTwig extends AbstractExtension {

  /**
   * Constructs the extension object.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    $functions[] = new TwigFunction('getLocationAutocomplete', [$this, 'getLocationAutocomplete']);
    $functions[] = new TwigFunction('getUrlParam', [$this, 'getUrlParam']);
    return $functions;
  }

  /**
   * Creates the location autocomplete.
   *
   * @return array
   *   The themed render array.
   */
  public function getLocationAutocomplete(): array {
    return [
      '#attached' => [
        'library' => ['usc_court_finder/location_autocomplete'],
      ],
      '#theme' => 'location_autocomplete',
    ];
  }

  /**
   * Get a value from url param.
   *
   * @param string $param
   *   The url param to get.
   *
   * @return string|null
   *   The value of the url param.
   */
  public function getUrlParam($param) {
    if ($param) {
      return $this->requestStack->getCurrentRequest()->query->get($param);
    }
    return '';
  }

}
