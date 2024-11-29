<?php

namespace Drupal\usc_liveness\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Controller for viewing Liveness page.
 */
class LivenessPageController extends ControllerBase {

  /**
   * Liveness page.
   *
   * @throws \InvalidArgumentException
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   */
  public function liveness() {

    try {

      $result = Database::getConnection()->query('SELECT 1')->fetchField();
      if ($result) {
        $data = [
          'date' => date(DATE_COOKIE),
        ];
        $headers = [
          'X-Robots-Tag' => 'noindex, nofollow',
        ];
        return new JsonResponse(data: $data, headers: $headers);
      }
      else {
        throw new ServiceUnavailableHttpException();
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Handle the case where the database connection fails.
      throw new ServiceUnavailableHttpException();
    }
  }

}
