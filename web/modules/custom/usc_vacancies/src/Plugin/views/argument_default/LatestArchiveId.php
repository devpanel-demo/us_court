<?php

namespace Drupal\usc_vacancies\Plugin\views\argument_default;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to provide the latest archive id.
 *
 * @\Drupal\views\Annotation\ViewsArgumentDefault(
 *   id = "latest_archive_id",
 *   title = @Translation("Latest Archive ID")
 * )
 */
class LatestArchiveId extends ArgumentDefaultPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LatestJobNodeId object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getArgument() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type', 'vacancy')
      ->condition('field_archive_id', 'usc_job', '!=')
      ->sort('field_archive_id', 'DESC')
      ->accessCheck(TRUE)
      ->range(0, 1);
    // @phpstan-ignore-next-line
    $nids = $query->execute();
    $field_archive_id = '';
    if (!empty($nids)) {
      $nid = reset($nids);
      $node = Node::load($nid);
      if (!$node->get('field_archive_id')->isEmpty()) {
        $field_archive_id = $node->get('field_archive_id')->value;
      }
    }

    return $field_archive_id;
  }

}
