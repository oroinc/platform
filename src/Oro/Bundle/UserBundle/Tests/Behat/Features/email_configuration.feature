@ticket-BAP-16638

Feature: Email configuration
  In order to have ability to change Email configuration
  As an Administrator
  I need to be able to make such changes

  Scenario: Add new mailbox
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I click "Add Mailbox"
    When I fill form with:
      | Mailbox Label | Test Mailbox     |
      | Email         | test@example.com |
    And I save form
    Then I should see "Test Mailbox has been saved" flash message
