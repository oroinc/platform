@regression
@ticket-BAP-18936
@fixture-OroFilterBundle:is_any_of_and_is_not_any_of_number_filters.yml
Feature: Is any of and is not any multienum filter
  In order to filter multienum filter on segments
  As an Administrator
  I need to be able to use "is any of" and "is not any of" multienum filters

  Scenario: Multi-Select field creation
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I filter Name as is equal to "User"
    And I click view User in grid
    And I click on "Create Field"
    And I fill form with:
      | Field Name | MultiSelectField |
      | Type       | Multi-Select     |
    And I click "Continue"
    And I set Options with:
      | Label |
      | AAA   |
      | BBB   |
      | CCC   |
    When I save and close form
    Then I should see "Field saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update first user
    Given I go to System/User Management/Users
    And click edit First in grid
    And I check "AAA"
    And I check "CCC"
    When I save and close form
    Then I should see "User saved" flash message

  Scenario: Update second user
    Given I go to System/User Management/Users
    And click edit Second in grid
    And I check "BBB"
    And I check "CCC"
    When I save and close form
    Then I should see "User saved" flash message

  Scenario: "is any of" condition
    Given I go to Reports & Segments/ Manage Segments
    And I click Edit Some segment name in grid
    And I add the following filters:
      | Field Condition | MultiSelectField | is any of | CCC |
    When I save and close form
    Then I should see following grid:
      | Username    |
      | first-user  |
      | second-user |

  Scenario: "is not any of" condition
    Given I go to Reports & Segments/ Manage Segments
    And I click Edit Some segment name in grid
    And I click on "Remove condition"
    And I add the following filters:
      | Field Condition | MultiSelectField | is not any of | AAA |
    When I save and close form
    Then I should see following grid:
      | Username    |
      | admin       |
      | second-user |
