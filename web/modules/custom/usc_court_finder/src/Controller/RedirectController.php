<?php

namespace Drupal\usc_court_finder\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a controller to handle the redirect.
 */
class RedirectController {

  /**
   * Redirects the user to a different URL.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @throws \InvalidArgumentException
   */
  public function redirectOldUrl() {
    return new RedirectResponse('/federal-court-finder/find');
  }

}
