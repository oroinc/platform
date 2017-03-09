Feature: Display settings manage
  In order to control system display behavior
  As Administrator
  I need to be able to change display settings parameters

  Scenario: Show/hide recent emails in user bar
    Given I login as administrator
    Then recent emails block must be visible
    When I go to System/Configuration
    And I click "Display settings"
    And I set configuration to:
      | Show recent emails | false |
    And I save form
    Then recent emails block should not be visible

  Scenario: Enable/disable WYSIWYG editor
    Given I go to Activities/Calendar Events
    And press "Create Calendar event"
    And I should see WYSIWYG editor
    When I go to System/Configuration
    And I click "Display settings"
    When I set configuration to:
      | Enable WYSIWYG editor | false |
    And I save form
    And I go to Activities/Cases
    And press "Create Case"
    Then I should not see WYSIWYG editor
