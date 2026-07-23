@ticket-BAP-23396

Feature: Entity Fields Grid Filters
  In order to efficiently navigate the entity fields list
  As an Administrator
  I want to filter the entity fields grid by field name.

  Scenario: Filter entity fields by field name
    Given I login as administrator
    And I go to System/Entities/Entity Management
    When I filter Name as is equal to "User"
    And I click view "User" in grid
    And I click "Fields"
    When I filter Name as contains "username"
    Then I should see "username" in grid
    Then I should see following records in grid:
      | username |
    And records in grid should be 1
    And I reset Name filter
