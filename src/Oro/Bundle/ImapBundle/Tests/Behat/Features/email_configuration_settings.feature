@ticket-BAP-16439

Feature: Email configuration settings
  In order to setup email synchronization settings
  As Administrator
  I need to be able to save SMTP settings without IMAP settings

  Scenario: Setup email SMTP configuration settings
    Given I login as administrator
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    When I fill "Email Synchronization Settings System Config Form" with:
      | Enable SMTP | true             |
      | SMTP Host   | smtp.gmail.com   |
      | SMTP Port   | 465              |
      | Encryption  | SSL              |
      | User        | user@example.org |
      | Password    | test_password    |
    And I save form
    Then I should see "Configuration saved" flash message
