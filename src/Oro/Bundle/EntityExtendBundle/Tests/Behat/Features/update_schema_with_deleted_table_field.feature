@ticket-BB-22635
@regression
Feature: Update schema with deleted table field
  In order to have correct schema when we have deleted old field (with update schema)
  As administrator
  I need to be able to create new fields for some entity, update schema, delete
  new field by field without update schema and see update schema button

  Scenario: Delete new field w/o update schema and check schema
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "Customer"
    And click View Customer in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name | Field1 |
      | Type       | String |
    And I click "Continue"
    And fill form with:
      | Label          | Field1 |
    And I save and close form
    And I should see "Update schema"
    And I click on "Create Field"
    And I fill form with:
      | Field name | Field2 |
      | Type       | String |
    And I click "Continue"
    And fill form with:
      | Label          | Field2 |
    And I save and close form
    And I should see "Update schema"
    When I click update schema
    Then I should see "Schema updated" flash message
    And filter Name as is equal to "Customer"
    And click View Customer in grid
    When I click remove "Field1" in grid
    And click "Yes"
    And I should see "Update schema"
    When I click remove "Field2" in grid
    And click "Yes"
    And I should see "Update schema"
