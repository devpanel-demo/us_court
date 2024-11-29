<?php

namespace Drupal\usc_court_finder\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;

/**
 * Variant of the 'integer' field for Court Type Weight.
 *
 * @FieldType(
 *   id = "court_type_weight",
 *   label = @Translation("Court Type Weight"),
 *   description = @Translation("An integer value based on the court type."),
 *   category = "number",
 *   default_widget = "number",
 *   default_formatter = "number_integer"
 * )
 */
class CourtTypeWeightItem extends IntegerItem {

  /**
   * Whether or not the value has been calculated.
   *
   * @var bool
   */
  protected $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \InvalidArgumentException
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \InvalidArgumentException
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \InvalidArgumentException
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Calculates the value of the field and sets it.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \InvalidArgumentException
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {

        $type = $entity->get('court_type')->value;

        switch ($type) {
          case 'District Court':
            $weight = 1;
            break;

          case 'Bankruptcy Court':
            $weight = 2;
            break;

          case 'Probation/Pretrial Services':
            $weight = 3;
            break;

          case 'Federal Defenders':
            $weight = 4;
            break;

          case 'Appeals Court':
            $weight = 0;
            break;

          default:
            $weight = 6;
            break;
        }

        $this->setValue(['value' => $weight]);
      }
      $this->isCalculated = TRUE;
    }
  }

}
