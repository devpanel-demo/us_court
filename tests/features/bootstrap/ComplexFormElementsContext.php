<?php

use Behat\Behat\Context\Context;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines application features from the specific context.
 */
class ComplexFormElementsContext extends RawDrupalContext implements Context {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * @When I fill autocomplete in :field with :text
   */
  public function iFillAutocompleteInWith($field, $text){
    $page = $this->getSession()->getPage();
    $input_autocomplete = $page->findField($field);
    $input_autocomplete->setValue($text);
    $this->getSession()->getDriver()->keyDown($input_autocomplete->getXpath(), '');
    $this->getSession()->wait(1000);
  }

  /**
   * Select autocomplete suggestion.
   *
   * @Then /^I select autosuggestion option "([^"]*)"$/
   *
   * @param string $text
   *   Option to be selected from autosuggestion.
   * @throws \InvalidArgumentException
   */
  public function selectAutosuggestionOption($text) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('xpath', '//li[normalize-space()="'. $text .'"]')
    );

    if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Cannot find text: "%s"', $text));
    }

    $element->click();
  }

  /**
   * @When /^I fill in wysiwyg field "([^"]*)" with "([^"]*)"$/
   */
  public function iFillInWysiwygFieldWith($locator, $value) {
    $el = $this->getSession()->getPage()->findField($locator);
    $fieldId = $el->getAttribute('data-ckeditor5-id');

    if (empty($fieldId)) {
      throw new Exception('Could not find an id for field with locator: ' . $locator);
    }
    $this->getSession()->executeScript("Drupal.CKEditor5Instances.get(\"$fieldId\").setData(\"$value\");");
  }

  /**
   * @When I fill date :label with :value
   */
  public function iFillDateWith($label, $value) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', '//label[@class="form-label"][normalize-space()="'. $label .'"]/following-sibling::input[@type="date"]')
    );
    if (NULL === $element) {
      $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('xpath', '//h4[@class="form-item__label"][normalize-space()="'. $label .'"]/following-sibling::div//input[@type="date"]')
      );
      if (NULL === $element) {
        throw new InvalidArgumentException(sprintf('Cannot find date field: "%s"', $label));
      }
    }
    $date_input_id = $element->getAttribute('id');
    $session->getDriver()->executeScript('document.getElementById("' . $date_input_id . '").value = "' . $value . '"');
    $session->getDriver()->executeScript('var event = new Event("change");document.getElementById("' . $date_input_id . '").dispatchEvent(event)');
  }

  /**
   * @Then I should not see the option :option in :select
   */
  public function iShouldNotSeeTheOptionIn($option, $select) {
    $page = $this->getSession()->getPage();
    $select_field = $page
      ->find('named', [
        'select',
        $select,
      ]
    );
    if ($select_field === NULL) {
      throw new InvalidArgumentException(sprintf('Select not found with id|name|label|value: "%s"', $select));
    }
    $option_field = $select_field
      ->find('named', [
      'option',
      $option,
    ]);
    if ($option_field !== NULL) {
      throw new InvalidArgumentException(sprintf('An option "%s" exists in select "%s", but it should not.', $option, $select));
    }
  }

  /**
   * @Then I should see the option :option in :select
   */
  public function iShouldSeeTheOptionIn($option, $select) {
    $page = $this->getSession()->getPage();
    $select_field = $page
      ->find('named', [
        'select',
        $select,
      ]
    );
    if ($select_field === NULL) {
      throw new InvalidArgumentException(sprintf('Select not found with id|name|label|value: "%s"', $select));
    }
    $option_field = $select_field
      ->find('named', [
      'option',
      $option,
    ]);
    if ($option_field === NULL) {
      throw new InvalidArgumentException(sprintf('An option "%s" does not exists in select "%s", but it should not.', $option, $select));
    }
  }

  /**
   * @When I check checkbox :label
   */
  public function iCheckCheckbox($label) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', '//label[normalize-space()="'. $label .'"]')
    );
    if (NULL === $element) {
      throw new InvalidArgumentException(sprintf('Cannot find checkbox with label: "%s"', $label));
    }
    $element->click();
  }

  /**
   * @When I check the facet :label
   */
  public function iCheckTheFacet($label) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', '//label[normalize-space()="'. $label .'"]')
    );
    if (NULL === $element) {
      throw new InvalidArgumentException(sprintf('Cannot find facet with label: "%s"', $label));
    }

    $parent = $element->getParent();
    $link = $parent->find('css', 'a');
    if (NULL === $link) {
      throw new InvalidArgumentException(sprintf('Cannot find facet with label: "%s"', $label));
    }
    $facet_id = $link->getAttribute('data-drupal-facet-item-value');
    $session->executeScript('document.querySelector("a[data-drupal-facet-item-value=\'' . $facet_id . '\']").style.display = "block"');
    $link->click();
  }

}
