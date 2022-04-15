@regression
@ticket-BAP-12348
@ticket-BB-20879
@automatically-ticket-tagged
Feature: Options in Grid View
  As an Administrator
  I want to be sure that 'Set As Default' value in 'Options' dropdown is displayed after the filter has been set as default
  So I set filter as default

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
    When I click Options in grid view
    And I should see "Set as default" in grid view options
    And I click on "Set as default" in grid view options
    Then I should see "View has been successfully updated" flash message
    And I should not see "Set as default" in grid view options

  Scenario: Create new default grid view
    When I filter Name as contains "Test default"
    And I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "Test View 2" in "name"
    And I check "Set as default"
    And I click "Save" in modal window
    Then I should see "View has been successfully created" flash message
    And I should not see "Set as default" in grid view options

  Scenario: Check name length validation
    Given I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" in "name"
    When I click "Save" in modal window
    Then I should see "This value is too long. It should have 255 characters or less."
    And I click "Cancel" in modal window

  Scenario: Grid page should open with default grid view
    Given I go to System/Entities/Entity Management
    Then I should see "Test View 2"
    And I should see "Name: contains \"Test default\""
    And I should not see "Set as default" in grid view options
