<?php

declare(strict_types=1);

namespace Drupal\usc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for US Courts: Core routes.
 */
final class VacanciesPathRedirectController extends ControllerBase {

  /**
   * Builds the redirection.
   *
   * @throws \Exception
   */
  public function __invoke($year, $month = NULL, $suffix = NULL, $format = NULL): RedirectResponse {
    if (NULL != $month && $suffix != NULL) {
      $url = Url::fromUserInput(sprintf('/judges-judgeships/judicial-vacancies/archive-judicial-vacancies/%s/%s/%s', $year, $month, $suffix));
    }
    else {
      $url = Url::fromUserInput('/judges-judgeships/judicial-vacancies/archive-judicial-vacancies', [
        'query' => [
          'year' => $year,
        ],
      ]);
    }

    return new RedirectResponse($url->toString());
  }

}
