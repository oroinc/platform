@fixture-OroDataGridBundle:grid-navigation.yml
Feature: Navigation in grid
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: User should see previous grid page, after deleting all records on current page
    Given I login as administrator
    When I go to System/ User Management/ Roles
    Then I should see following records in grid:
      | Account Manager |
      | Buyer1          |
      | Buyer5          |
      | Sales Rep       |
    And there are 14 records in grid
    When I select 10 from per page list dropdown
    Then I should see following records in grid:
      | Account Manager   |
      | Buyer1            |
      | Buyer5            |
      | Marketing Manager |
    When I press next page button
    Then I should see following records in grid:
      | Online Sales Rep |
      | Sales Rep        |
    When I check All Visible records in grid
    And click Delete mass action
    And confirm deletion
    Then I should see following records in grid:
      | Account Manager   |
      | Buyer1            |
      | Buyer5            |
      | Marketing Manager |
    And there are 10 records in grid
