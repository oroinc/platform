@regression
@ticket-BAP-17964

Feature: Update schema with not accessible entity
  In order to allow an administrator to keep not finished schema changes as is and apply schema for completed entities
  As an Administrator
  I want to update schema even if there are entities I did not finish

  Scenario: Background
    Given I login as administrator

  Scenario: Enable advanced options for extend entities
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    When I click Edit User in grid
    And select "Yes" from "Enable Comments"
    And select "Yes" from "Enable Attachments"
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message

  Scenario: Complete extend entity and update schema
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "Group"
    When I click View Group in grid
    And click "Create Field"
    And fill form with:
      | Field name | TestName   |
      | Type       | String |
    And click "Continue"
    And save and close form
    Then I should see "Field saved" flash message
    And should see "Update Schema"
    When I click update schema
    Then I should see Schema updated flash message
