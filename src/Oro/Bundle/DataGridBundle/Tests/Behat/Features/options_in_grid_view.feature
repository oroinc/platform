@regression
@ticket-BAP-12348
@automatically-ticket-tagged
Feature: Options in Grid View
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Create new not default grid view
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I should not see "Set as default" in grid view options
    When I filter Name as contains "Test not default"
    And I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "Test View 1" in "name"
    And I click "Save" in modal window
    Then I should see "View has been successfully created" flash message
    And I should see "Set as default" in grid view options

  Scenario: Set as default in grid view
    Given I go to System/Entities/Entity Management
    When I click Options in grid view
    And I should see "Set as default" in grid view options
    And I click on "Set as default" in grid view options
    Then I should see "View has been successfully updated" flash message
    And I should not see "Set as default" in grid view options

  Scenario: Grid page should open with default grid view
    Given I go to System/Entities/Entity Management
    Then I should see "Test View 1"
    And I should see "Name: contains \"Test not default\""

  Scenario: Create new default grid view
    Given I go to System/Entities/Entity Management
    And I should not see "Set as default" in grid view options
    When I filter Name as contains "Test default"
    And I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "Test View 2" in "name"
    And I check "Set as default"
    And I click "Save" in modal window
    Then I should see "View has been successfully created" flash message
    And I should not see "Set as default" in grid view options
