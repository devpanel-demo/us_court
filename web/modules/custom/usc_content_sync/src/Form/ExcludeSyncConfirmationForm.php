<?php

namespace Drupal\usc_content_sync\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\usc_content_sync\NodeSyncService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements a confirmation form.
 */
class ExcludeSyncConfirmationForm extends ConfirmFormBase {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The import service.
   *
   * @var \Drupal\usc_content_sync\NodeSyncService
   */
  protected $importService;

  /**
   * Constructs a new ExcludeSyncConfirmationForm instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\usc_content_sync\NodeSyncService $import_service
   *   The content sync service.
   */
  public function __construct(RequestStack $request_stack, NodeSyncService $import_service) {
    $this->requestStack = $request_stack;
    $this->importService = $import_service;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('usc_content_sync.sync_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the node from the route.
    $this->node = $this->getRouteMatch()->getParameter('node');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exclude_sync_confirmation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // This will be the question displayed to the user.
    return $this->t('Are you sure you want to proceed?');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getCancelUrl() {
    // Get the referrer URL.
    $referer = $this->requestStack->getCurrentRequest()->headers->get('referer');

    if ($referer) {
      return Url::fromUri($referer);
    }

    // If no referrer, fall back to a default route.
    return new Url('usc_content_sync.content_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // You can add a description here for additional context.
    return $this->t('This node will not be synced on the default content anymore.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    // Define the text for the confirmation button.
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    // Define the text for the cancel button.
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $referer = $this->requestStack->getCurrentRequest()->headers->get('referer');
    $this->importService->deleteFile($this->node);
    if ($referer) {
      $form_state->setRedirect($referer);
    }
    $form_state->setRedirect('usc_content_sync.content_overview');
  }

}
