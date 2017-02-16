@fixture-users_group_crud.yml
Feature: Users groups
  In order to keep organization users organized
  As a Sales Manager
  I want to be able to manage groups and users in it

  Scenario: Create user group
    Given I login as administrator
    When I go to System/ User Management/ Groups
    And I press "Create Group"
    And I check first 5 records in 1 column
    When I save and close form
    Then I should see validation errors:
      | Name      | This value should not be blank.  |
    And I fill in "Name" with "First test group"
    When I save and close form
    Then I should see First test group in grid

  Scenario: Check users in created group
    Given I click Edit First test group in grid
    When I check "Yes" in Has Group filter
    Then I should see following records in grid:
      | John    |
      | Alice1  |
      | Alice2  |
      | Alice3  |
      | Alice4  |
