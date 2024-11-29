<?php

namespace Drupal\usc_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks access for the main US Courts admin menu.
 */
class MenuAccess implements ContainerInjectionInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUserService;

  /**
   * Creates a new MenuAccess objects.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user_service
   *   The current user service.
   */
  public function __construct(AccountProxy $current_user_service) {
    $this->currentUserService = $current_user_service;
  }

  /**
   * Instantiate the Access check.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   *   Throw a circular reference exception.
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   *   Throw a not found exception.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Checks access for the user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessUscMenu() {
    $account = $this->currentUserService;

    // No chance anon sees this.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    if ($account->id() === 1) {
      return AccessResult::allowed();
    }

    // If an account can access configurations, it should be allowed.
    return AccessResult::allowedIf($account->hasPermission('access uscourts configurations'));
  }

}
