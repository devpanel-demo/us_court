<?php

namespace Drupal\usc_court_finder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of CourtFinderItem entities.
 */
class CourtFinderItemListBuilder extends EntityListBuilder {

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
    /* @var $entity \Drupal\usc_court_finder\Entity\CourtFinderItem */
    $row['label'] = Link::createFromRoute($entity->label(), 'entity.usc_court_finder_item.canonical', [
      'usc_court_finder_item' => $entity->id(),
    ]);
    return $row + parent::buildRow($entity);
  }

}
