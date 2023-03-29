@behat-test-env
Feature: Email SMTP settings with enabled account types
  In order to manager Email setting of application
  As an Administrator
  I need to be able to check SMTP settings if additional account types is enabled

  Scenario: Feature Background
    Given I enable Google IMAP
    And I login as administrator

  Scenario: Check SMTP settings in user configuration with correct parameters
    Given I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    Then I should see "Account Type"
    And I should not see "Enable IMAP"
    And I should not see "Enable SMTP"
    When I select "Other" from "Account Type"
    And I fill "Email Synchronization Settings System Config Form Other" with:
      | Enable SMTP | true             |
      | SMTP Host   | smtp.example.org |
      | SMTP Port   | 2525             |
      | Encryption  | SSL              |
      | User        | test_user        |
      | Password    | test_password    |
    When I click "Check connection/Retrieve folders"
    Then I should see "SMTP connection is established successfully"
