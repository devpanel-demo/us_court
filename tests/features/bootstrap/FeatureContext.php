<?php

use Behat\Behat\Context\Context;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context {

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
   * @Then /^I should see "(?P<text>.+)" in the first result$/
   */
  public function iShouldSeeInTheFirstResult($text) {
    // Get the first matched element with the specified CSS selector.
    $elements = $this->getSession()->getPage()->findAll('css', '.views-row');
    if ($elements) {
      if (!$elements[0]->find('named', array('content', $text))) {
        throw new \InvalidArgumentException(sprintf('Cannot find text: "%s"', $text));
      }
    } else {
      throw new \InvalidArgumentException(sprintf('Cannot find results.'));
    }
  }

  /**
   * @Then /^I should see the link "(?P<text>.+)" in the first result$/
   */
  public function iShouldSeeTheLinkInTheFirstResult($text) {
    $elements = $this->getSession()->getPage()->findAll('css', '.views-row');
    if ($elements) {
      if (!$elements[0]->find('named', array('link', $text))) {
        throw new \InvalidArgumentException(sprintf('Cannot find link: "%s"', $text));
      }
    } else {
      throw new \InvalidArgumentException(sprintf('Cannot find results.'));
    }
  }

  /**
   * @When I wait :seconds seconds
   */
  public function iWaitSeconds($seconds) {
    sleep($seconds);
  }

  /**
   * @When I swith to new tab
   */
  public function iSwithToNewTab() {
    $session = $this->getSession();
    $windowNames = $session->getWindowNames();
    if(sizeof($windowNames) < 2){
      throw new \ErrorException("Expected to see at least 2 windows opened.");
    }

    // Switch to that window.
    $session->switchToWindow($windowNames[1]);
  }

  /**
   * @Given /^I close the current (?:window|tab)$/
   */
  public function closeCurrentWindow() {
    $session = $this->getSession();
    // Switch to that window.
    $session->switchToWindow();
  }

  /**
   * @When I scroll :elementId into view
   */
  public function scrollIntoView($elementId) {
    $function = <<<JS
      (function(){
        var elem = document.getElementById("$elementId");
        elem.scrollIntoView(true);
      })()
      JS;
    try {
      $this->getSession()->executeScript($function);
    }
    catch(Exception $e) {
      throw new \Exception("ScrollIntoView failed");
    }
  }

  /**
   * @When I open details on :link
   */
  public function iOpenDetailsOn($link) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', '//summary[@class="details__summary"][normalize-space()="' . $link . '"]')
    );
    if (NULL === $element) {
      throw new InvalidArgumentException(sprintf('Cannot find details element: "%s"', $link));
    }
    $element->click();
  }

}
