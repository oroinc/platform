@ticket-BAP-12990
Feature: Check JS Tree in sidebar
  Clear search value

  Scenario: Type and clear search value
    Given I login as administrator
    And I go to System/Configuration
    When I type "General" in "Js Tree Search"
    And I should see a "Clear Search Value Button" element
    Then I should see "General"
    And I click "Clear Search Value Button"
    Then Js Tree Search field is empty
