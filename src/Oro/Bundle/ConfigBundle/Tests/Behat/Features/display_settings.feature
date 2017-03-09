Feature: Display settings manage
  In order to control system display behavior
  As Administrator
  I need to be able to change display settings parameters

  Scenario: Show/hide recent emails in user bar
    Given I login as administrator
    Then element ".email-notification-menu" must be visible
    When I go to System/Configuration
    And I click "Display settings"
    And I set configuration to:
      | Show recent emails | false |
    And I save form
    Then element ".email-notification-menu" should not be visible
