<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\extra_field_plus\Plugin\ExtraFieldPlusDisplayBase;

/**
 * Label Extra field with formatted output.
 *
 * @ExtraFieldDisplay(
 *   id = "court_finder_label",
 *   label = @Translation("Court Finder label"),
 *   bundles = {
 *     "usc_location.*",
 *   },
 *   visible = false
 * )
 */
class CourtFinderLabel extends ExtraFieldPlusDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Court Finder label');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelDisplay() {
    return 'hidden';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function view(ContentEntityInterface $entity) {
    $settings = $this->getEntityExtraFieldSettings();

    $link_to_entity = $settings['link_to_entity'];
    $wrapper = $settings['wrapper'];
    $label = $entity->get('office_name')->value;
    // Add city.
    if (!$entity->get('building_city')->isEmpty()) {
      $label .= ' - ' . $entity->get('building_city')->value;
    }
    // Add state.
    if (!$entity->get('building_state')->isEmpty()) {
      $label .= ', ' . $entity->get('building_state')->value;
    }

    $url = NULL;
    if ($link_to_entity) {
      $url = $entity->toUrl()->setAbsolute();
    }

    if ($url) {
      $element = [
        '#type' => 'html_tag',
        '#tag' => $wrapper,
        'link' => [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $url,
        ],
      ];
    }
    else {
      $element = [
        '#type' => 'html_tag',
        '#tag' => $wrapper,
        '#value' => $label,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  protected static function extraFieldSettingsForm(): array {
    $form = parent::extraFieldSettingsForm();

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Link to the entity'),
    ];

    $form['wrapper'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Wrapper'),
      '#options' => [
        'span' => 'span',
        'p' => 'p',
        'h1' => 'h1',
        'h2' => 'h2',
        'h3' => 'h3',
        'h4' => 'h4',
        'h5' => 'h5',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected static function defaultExtraFieldSettings(): array {
    $values = parent::defaultExtraFieldSettings();

    $values += [
      'link_to_entity' => FALSE,
      'wrapper' => 'span',
    ];

    return $values;
  }

}
