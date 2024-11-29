<?php

namespace Drupal\usc_core\Plugin\SingleContentSyncFieldProcessor;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\single_content_sync\SingleContentSyncFieldProcessorPluginBase;

/**
 * Plugin implementation of the simple field processor plugin.
 *
 * @SingleContentSyncFieldProcessor(
 *   id = "faqfield",
 *   label = @Translation("FAQ Field"),
 *   field_type = "faqfield",
 * )
 */
class FAQField extends SingleContentSyncFieldProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function exportFieldValue(FieldItemListInterface $field): array {
    return $field->getValue();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function importFieldValue(FieldableEntityInterface $entity, string $fieldName, array $value): void {
    $entity->set($fieldName, $value);
  }

}
