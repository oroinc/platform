@behat-test-env
@ticket-BAP-15487
@ticket-BAP-17649

Feature: Email SMTP settings
  In order to manager Email setting of application
  As an Administrator
  I need to be able to check SMTP settings

  Scenario: Check SMTP settings in system configuration with incomplete parameters
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use default" for "Port" field
    When I fill form with:
      | Port | 33 |
    When I scroll to top
    And I save form
    Then I should see "Could not establish the SMTP connection" error message
    When I click "Check Connection (Saved Settings)"
    Then I should see "Could not establish connection" error message

  Scenario: Check SMTP settings in system configuration with incorrect parameters
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use default" for "Host" field
    And uncheck "Use default" for "Port" field
    And uncheck "Use default" for "Encryption" field
    And uncheck "Use default" for "Username" field
    And uncheck "Use default" for "Password" field
    When I fill form with:
      | Host | sm               |
      | Port | wrong_port_value |
    Then I should see validation errors:
      | Host | This value is too short. It should have 3 characters or more. |
      | Port | This value should be a valid number.                          |
    And I fill form with:
      | Host       | smtp.example.org |
      | Port       | 2525             |
      | Encryption | SSL              |
      | Username   | unknown          |
      | Password   | unknown          |
    When I click "Check Connection (New Settings)"
    Then I should see "Could not establish connection"

  Scenario: Check SMTP settings in system configuration with correct parameters
    Given I fill form with:
      | Username | test_user     |
      | Password | test_password |
    When I click "Check Connection (Saved Settings)"
    Then I should see "Could not establish connection"
    When I click "Check Connection (New Settings)"
    Then I should see "Connection established successfully"
    And I save form

  Scenario: Try to save incorrect SMTP settings in system configuration
    Given I fill form with:
      | Encryption | TLS |
    When I click "Save settings"
    Then I should see "Could not establish the SMTP connection"

  Scenario: Save form with incomplete SMTP settings in user configuration
    Given I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I fill "Email Synchronization Settings System Config Form" with:
      | Enable SMTP | true         |
      | SMTP Host   | somehost.com |
      | SMTP Port   | 33           |
      | User        | WrongUser    |
    When I save form
    Then I should see "Could not establish the SMTP connection"

  Scenario: Check SMTP settings in user configuration with incorrect parameters
    When I fill "Email Synchronization Settings System Config Form" with:
      | Enable SMTP | true             |
      | SMTP Host   | sm               |
      | SMTP Port   | wrong_port_value |
    Then I should see validation errors:
      | SMTP Host | This value is too short. It should have 3 characters or more. |
      | SMTP Port | This value should be of type integer.                         |
    When I fill "Email Synchronization Settings System Config Form" with:
      | Enable SMTP | true             |
      | SMTP Host   | smtp.example.org |
      | SMTP Port   | wrong_port_value |
      | Encryption  | SSL              |
      | User        | unknown          |
      | Password    | unknown          |
    And I click "Check connection/Retrieve folders"
    Then I should see "Parameters are not valid"
    When I fill "Email Synchronization Settings System Config Form" with:
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

  Scenario: Try to save incorrect SMTP settings in user configuration
    Given I fill "Email Synchronization Settings System Config Form" with:
      | Encryption | TLS |
    When I save form
    Then I should see "Could not establish the SMTP connection"
