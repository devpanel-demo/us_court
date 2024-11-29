<?php

namespace Drupal\usc_court_finder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the all Court Finder entities.
 *
 * @see \Drupal\usc_court_finder\Entity.
 */
class CourtFinderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\usc_court_finder\Entity\CourtFinderItemInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view court_finder_content');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit court_finder_content');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete court_finder_content');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create court_finder_content');
  }

}
