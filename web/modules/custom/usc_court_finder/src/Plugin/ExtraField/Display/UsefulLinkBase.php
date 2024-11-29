<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;

/**
 * Court Useful Link Extra field base.
 */
abstract class UsefulLinkBase extends ExtraFieldDisplayBase implements UsefulLinkInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function view(ContentEntityInterface $entity) {
    $field = $this->getFieldName();
    // If empty field then return empty.
    if ($entity->get($field)->isEmpty()) {
      return [];
    }

    $url = Url::fromUri($entity->get($field)->value, [
      'absolute' => TRUE,
    ]);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [Html::cleanCssIdentifier($this->getPluginId())]
      ],
      'link' => [
        '#type' => 'link',
        '#title' => $this->getLabel(),
        '#url' => $url,
      ],
      'text' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->getDescription(),
      ],
    ];
  }

}
