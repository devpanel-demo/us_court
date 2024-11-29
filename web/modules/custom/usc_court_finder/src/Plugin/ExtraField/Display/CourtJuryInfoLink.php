<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

/**
 * Court Jury Info Link Extra field.
 *
 * @ExtraFieldDisplay(
 *   id = "court_finder_court_jury_info_link",
 *   label = @Translation("Court Jury Info Link"),
 *   bundles = {
 *     "usc_location.*",
 *   },
 *   visible = false
 * )
 */
class CourtJuryInfoLink extends UsefulLinkBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return 'jury_service_url';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Jury Information');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t("Each district court summons eligible citizens within the local area. Find out more information about jury service for this particular court.");
  }

}
