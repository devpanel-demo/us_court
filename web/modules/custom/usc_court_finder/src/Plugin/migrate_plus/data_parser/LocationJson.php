<?php

declare(strict_types=1);

namespace Drupal\usc_court_finder\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain Location JSON data for migration.
 *
 * @DataParser(
 *   id = "usc_location_json",
 *   title = @Translation("USC Location JSON")
 * )
 */
class LocationJson extends Json {

  /**
   * Retrieves the JSON data and returns it as an array.
   *
   * @param string $url
   *   URL of a JSON feed.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getSourceData(string $url): array {

    $source_data = parent::getSourceData($url);
    $return_data = [];

    foreach ($source_data as $item) {
      if ($this->includeInImport($item)) {
        $return_data[] = $item;
      }
    }

    return $return_data;
  }

  /**
   * Checks if this location should be included in the import.
   */
  protected function includeInImport(array $location): bool {

    $unit = '';
    $org_code = $location['OrgCode'] ?? '';
    if ($org_code && strlen($org_code) === 7) {
      $unit = substr($org_code, 0, 1) . substr($org_code, 6, 1);
    }

    // Filter out Bankruptcy Appellate Panels if they're not the main
    // location.
    if ($unit === 'AB' && isset($location['MainOffice']) && $location['MainOffice'] == FALSE) {
      return FALSE;
    }

    // Filter out libraries if they're not open to the public.
    if ($unit === 'AL' && isset($location['HasPublicLibrary']) && $location['HasPublicLibrary'] == FALSE) {
      return FALSE;
    }

    // Filter out Nat'l IT Operations & Applications Division.
    if ($unit === 'FU') {
      return FALSE;
    }

    // Filter out records without Latitude or Longitude.
    if (isset($location['Latitude']) && isset($location['Longitude']) && ($location['Latitude'] === '' || $location['Longitude'] === '')) {
      return FALSE;
    }

    // Filter out "Chambers only" locations.
    if (isset($location['ChambersOnly']) && $location['ChambersOnly']) {
      return FALSE;
    }

    // Filter out "Hide from public" locations.
    if (isset($location['HideFromPublic']) && $location['HideFromPublic']) {
      return FALSE;
    }

    return TRUE;
  }

}
