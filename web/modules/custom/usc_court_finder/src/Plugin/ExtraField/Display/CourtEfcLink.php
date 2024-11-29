<?php

namespace Drupal\usc_court_finder\Plugin\ExtraField\Display;

/**
 * Court ECF Link Extra field.
 *
 * @ExtraFieldDisplay(
 *   id = "court_finder_court_ecf_link",
 *   label = @Translation("Court ECF Link"),
 *   bundles = {
 *     "usc_location.*",
 *   },
 *   visible = false
 * )
 */
class CourtEfcLink extends UsefulLinkBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return 'ecf_url';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Electronic Case Filing');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t("Access this court's Case Management/Electronic Case Files (CM/ECF) log-in page. The federal judiciary's CM/ECF system allows case documents, such as pleadings, motions, and petitions, to be filed with the court online.");
  }

}
