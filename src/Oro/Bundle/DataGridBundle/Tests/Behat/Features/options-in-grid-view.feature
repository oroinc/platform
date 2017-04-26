Feature: Options in Grid View

  Scenario: Create new grid view
    Given I login as administrator
    And I go to System/Entities/Entity Management
    When I filter Name as contains "Test"
    And I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "Test View" in "name"
    And press "Save"
    Then I should see "View has been successfully created" flash message

  Scenario: Set as default in grid view
    Given I go to System/Entities/Entity Management
    When I click Options in grid view
    And I should see "Set as default" in grid view options
    And I click on "Set as default" in grid view options
    Then I should see "View has been successfully updated" flash message
    And I should not see "Set as default" in grid view options

  Scenario: Grid page should open with default grid view
    Given I go to System/Entities/Entity Management
    Then I should see "Test View"
    And I should see "Name: contains \"Test\""
