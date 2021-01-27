@ticket-BAP-18839
@regression
Feature: Number range filter with empty and not empty value
  In order to filter "empty" and "not empty" by list
  As administrator
  I need to be able to use "empty" and "empty" number range filters

  Scenario: Apply empty and not empty filter to users list to dynamic field with type currency
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name | Test1 |
      | Type       | Money |
    And I click "Continue"
    And fill form with:
      | Show grid filter | Yes |
    And I save and close form
    Then click update schema
    And go to System/User Management/Users
    And I filter Test1 as is empty
    Then I should see following grid:
      | Username |
      | admin    |
    And filter Test1 as is not empty
    Then there is no records in grid
