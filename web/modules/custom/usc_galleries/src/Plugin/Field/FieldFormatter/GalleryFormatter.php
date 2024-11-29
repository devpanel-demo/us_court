<?php

namespace Drupal\usc_galleries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'Gallery' formatter.
 *
 * @FieldFormatter(
 *   id = "usc_galleries_gallery",
 *   label = @Translation("Gallery"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GalleryFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $elements['#attached']['library'][] = 'usc_galleries/gallery';
    return $elements;
  }

}
