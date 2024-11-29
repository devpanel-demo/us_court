<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;

/**
 * Maps URL Extra field.
 *
 * @ExtraFieldDisplay(
 *   id = "court_finder_maps_url",
 *   label = @Translation("Court Finder Maps Url"),
 *   bundles = {
 *     "usc_location.*",
 *   },
 *   visible = false
 * )
 */
class MapsUrl extends ExtraFieldDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    // Remove Office Center.
    $address = str_replace("Office Center", '', $entity->get('address')->value);
    // Change line breaks to commas.
    $address = str_replace("\r\n", ', ', $address);

    return ['#markup' => "https://www.google.com/maps?saddr=Current+Location&daddr=$address"];
  }

}
