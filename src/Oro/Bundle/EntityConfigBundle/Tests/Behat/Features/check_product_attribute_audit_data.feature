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
    Then I should see following "Product Attribute Audit Grid" grid:
      | Diffs                                                                                                                                                                                                                                  |
      | Contact information: State: New Is serialized: 1 Is attribute: 1 Is global: 0 Field name: StringAttribute Organization id: 1 Is visible: 3 Show filter: 0 Order: Priority: Searchable: 0 Title field: 0 Order: Identity: 0 Excluded: 0 |

