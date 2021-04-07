@regression
@ticket-BAP-20385

Feature: Check product attribute audit data
  In order to be able to see the audit history of product attribute
  As an administrator
  I create a new product attribute and see that the audit history displays valid data

  Scenario: Create string product attribute
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    When I fill form with:
      | Field Name | StringAttribute |
      | Type       | String          |
    And I click "Continue"
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Check grid of attribute audit
    Given I click Edit "StringAttribute" in grid
    When I click "Change History"
    Then I should see text matching "State: New"
    And should not see text matching "State: Active New"
    And should see text matching "Field name: StringAttribute"
    And should see text matching "Is attribute: 1"
    And should see text matching "Is serialized: 1"
    And should see text matching "Is global: 0"
    And should see text matching "Organization id: 1"
    And should see text matching "Is visible: 3"
    And should see text matching "Show filter: 0"
    And should see text matching "Searchable: 0"
    And should see text matching "Title field: 0"
    And should see text matching "Identity: 0"
    And should see text matching "Excluded: 0"
