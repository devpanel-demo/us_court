@api
Feature: Homepage

  Scenario: A user can see a homepage
    Given I am on "/"
    Then the response status code should be 200
