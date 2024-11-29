<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

/**
 * Court EJuror Link Extra field.
 *
 * @ExtraFieldDisplay(
 *   id = "court_finder_court_ejuror_link",
 *   label = @Translation("Court eJuror Link"),
 *   bundles = {
 *     "usc_location.*",
 *   },
 *   visible = false
 * )
 */
class CourtEjurorLink extends UsefulLinkBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return 'ejuror_url';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('eJuror');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t("Were you summoned for federal jury duty? Fill out your jury qualification questionnaire or summons online through the court's eJuror system.");
  }

}
