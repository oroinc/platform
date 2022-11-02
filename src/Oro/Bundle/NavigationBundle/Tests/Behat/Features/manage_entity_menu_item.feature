@regression
@ticket-BB-16829
Feature: Manage entity Menu Item
  In order to update "Menu Item" entity
  As an Administrator
  I want to be able to load the page with "Menu Item" entity in Entity Management

  Scenario: Check page "Manage entity" loads without any errors
    Given I login as administrator
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "MenuUpdate"
    And I click view Menu Item in grid
    Then I should not see "There was an error performing the requested operation" flash message
    And I should see "Create Field"
    When I click "Number of records"
    Then I should be on Menus page
