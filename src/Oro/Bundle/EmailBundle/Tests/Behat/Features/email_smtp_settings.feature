@ticket-BAP-15487

Feature: Email SMTP settings
  In order to manager Email setting of application
  As an Administrator
  I need to be able to check SMTP settings

  Scenario: Check SMTP settings in system configuration with incorrect parameters
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use default" for "Host" field
    And uncheck "Use default" for "Port" field
    And uncheck "Use default" for "Encryption" field
    And uncheck "Use default" for "Username" field
    And uncheck "Use default" for "Password" field
    And I fill form with:
      | Host       | smtp.example.org |
      | Port       | 2525             |
      | Encryption | SSL              |
      | Username   | unknown          |
      | Password   | unknown          |
    When I click "Check SMTP Connection"
    Then I should see "Could not establish connection"

  Scenario: Check SMTP settings in system configuration with correct parameters
    Given I fill form with:
      | Username | test_user     |
      | Password | test_password |
    When I click "Check SMTP Connection"
    Then I should see "Connection established successfully"
    And I save form

  Scenario: Check SMTP settings in user configuration with incorrect parameters
    Given I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I fill "Email Synchronization Settings System Config Form" with:
      | Enable SMTP | true             |
      | SMTP Host   | smtp.example.org |
      | SMTP Port   | 2525             |
      | Encryption  | SSL              |
      | User        | unknown          |
      | Password    | unknown          |
    When I click "Check connection/Retrieve folders"
    Then I should see "Could not establish the SMTP connection"

  Scenario: Check SMTP settings in user configuration with correct parameters
    Given I fill form with:
      | User     | test_user     |
      | Password | test_password |
    When I click "Check connection/Retrieve folders"
    Then I should see "SMTP connection is established successfully"
    And I save form
