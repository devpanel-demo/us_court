<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

/**
 * Court Page Link Extra field.
 *
 * @ExtraFieldDisplay(
 *   id = "court_finder_court_page_link",
 *   label = @Translation("Court Page Link"),
 *   bundles = {
 *     "usc_location.*",
 *   },
 *   visible = false
 * )
 */
class CourtPageLink extends UsefulLinkBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return 'url';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Visit Court Website');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t("Access this court's website for more information on filing a case, information for litigants and attorneys, and learning about the court.");
  }

}
