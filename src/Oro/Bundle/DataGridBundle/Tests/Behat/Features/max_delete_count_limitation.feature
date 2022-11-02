@ticket-BAP-9607
@fixture-OroDataGridBundle:groups_data.yml

Feature: Max delete count limitation
  In order to avoid extra memory usage during entities deletion
  As an User
  I should be able to delete only 100 records at once

  Scenario: Delete all records in grid should remove only 100 first records
    Given I login as administrator
    When I go to System/ User Management/ Groups
    Then number of records should be 108
    When I filter Name as Contains "Test-"
    And I click "GridFiltersButton"
    Then number of records should be 105
    When I check all records in grid
    And I click Delete mass action
    And I confirm deletion
    Then I should see following records in grid:
      | Test-101   |
      | Test-102   |
      | Test-103   |
      | Test-104   |
      | Test-105   |
    And there are 5 records in grid
