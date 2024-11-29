<?php

namespace Drupal\usc_court_finder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Location entities.
 */
class LocationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\usc_court_finder\Entity\Location */
    $row['label'] = Link::createFromRoute($entity->label(), 'entity.usc_location.canonical', [
      'usc_location' => $entity->id(),
    ]);
    return $row + parent::buildRow($entity);
  }

}
