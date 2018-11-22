@regression
@ticket-BAP-13787
@automatically-ticket-tagged
@fixture-OroUserBundle:users_group_crud.yml
Feature: Users groups
  In order to keep organization users organized
  As a Sales Manager
  I want to be able to manage groups and users in it

  Scenario: Create user group
    Given I login as administrator
    When I go to System/ User Management/ Groups
    And I click "Create Group"
    And I check first 5 records in 0 column
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
    Then I save and close form

  Scenario: Edit users group name
    Given I click Edit First test group in grid
    And I fill in "Name" with ""
    And I save and close form
    Then I should see validation errors:
      | Name     | This value should not be blank.  |
    When I fill in "Name" with "Edited test group"
    And I save and close form

  Scenario: Remove users from user group
    Then I should see Edited test group in grid
    When I click Edit Edited test group in grid
    And I uncheck first 5 records in 0 column
    And I save and close form
    Then I should see Edited test group in grid
    When I click Edit Edited test group in grid
    And I check "Yes" in Has Group filter
    And there is no records in grid
    And I save and close form

  Scenario: Add users to group
    Then I should see Edited test group in grid
    When I click Edit Edited test group in grid
    And I check first 4 records in 0 column
    And I save and close form
    Then I should see Edited test group in grid
    When I click Edit Edited test group in grid
    And I check "Yes" in Has Group filter
    Then I should see following records in grid:
      | John    |
      | Alice1  |
      | Alice2  |
      | Alice3  |
    And I should not see "Alice4"
    And I save and close form

  Scenario: Case deletion
    Given there is 4 records in grid
    When I click Delete Edited test group in grid
    When I confirm deletion
    Then I should see "Item deleted" flash message
    And there is 3 records in grid
    And I should not see "Edited test group"
