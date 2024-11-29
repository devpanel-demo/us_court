@javascript
@api
Feature: Court Finder Block

  Scenario: A visitor I can search by court name.
    Given I am an anonymous user
    When I am on the homepage
    Then I should see the text "Find a Federal Court" in the "homepage_hero"
    When I check checkbox "Court name"
    And I fill autocomplete in "Search by court, circuit, district, or building" with "texas"
    When I select autosuggestion option "Eastern District of Texas"
    # Results page.
    # Filters.
    Then I should see the text "Court Type"
    And I should see the text "Closest Districts"
    And I should see the text "Refine Search"
    # Finder.
    And I should see the text "Find a Federal Court"
    And the "Search by court, circuit, district, or building" field should contain "Eastern District of Texas"
    # Results.
    And I should see text matching "Displaying .*"
    And I should see "Texas Eastern Bankruptcy Court - Beaumont, TX" in the first result
    And I should see "Jack Brooks Federal Building and United States Courthouse" in the first result
    And I should see "300 Willow Street" in the first result
    And I should see "Beaumont, TX 77701" in the first result
    And I should see "Bankruptcy Court" in the first result
    And I should see the link "Go to next page"

  Scenario: A visitor I can search by location.
    Given I am an anonymous user
    When I am on the homepage
    Then I should see the text "Find a Federal Court" in the "homepage_hero"
    And I fill autocomplete in "Search by address, city, state, or zip" with "Culp"
    When I select autosuggestion option "Culpeper, VA, USA"
    And I wait "3" seconds
    # Results page.
    # Filters.
    Then I should see the text "Court Type"
    And I should see the text "Closest Districts"
    And I should see the text "Refine Search"
    # Finder.
    And I should see the text "Find a Federal Court"
    And the "Search by address, city, state, or zip" field should contain "Culpeper, VA 22701, USA"
    # Results.
    And I should see text matching "Displaying .*"
    And I should see "34.8 mi" in the first result
    And I should see "Virginia Eastern Probation Office - Manassas, VA" in the first result
    And I should see "8809 Sudley Road, Suite 200" in the first result
    And I should see "Manassas, VA 20110-4749" in the first result
    And I should see "Probation/Pretrial Services" in the first result
    And I should see the link "Get Directions" in the first result
    And I should see the link "703-366-2100" in the first result
    And I should see the link "Go to next page"
    # Refine search.
    When I open details on "Closest Districts"
    And I check the facet "Western District of Virginia (19)"
    # Then I should see "40.5 mi" in the first result
    # And I should see "Virginia Western Federal Public Defender - Charlottesville, VA" in the first result
    # When I check the facet "District Court (6)"
    # Then I should see "40.7 mi" in the first result
    # And I should see "Virginia Western District Court - Charlottesville, VA" in the first result
