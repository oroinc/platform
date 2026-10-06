@regression
@behat-test-env
@ticket-BB-27835

Feature: Unauthenticated SMTP connection in system configuration
  In order to send emails through an SMTP server that does not require authentication
  As an Administrator
  I need to be able to check and save SMTP settings with only the host and the port specified

  Scenario: Check connection with new settings when encryption, username and password use the default values
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use default" for "Host" field
    And uncheck "Use default" for "Port" field
    When I fill form with:
      | Host | smtp.example.org |
      | Port | 2525             |
    And I click "Check Connection (New Settings)"
    Then I should see "Connection established successfully"
    And I should not see "Could not establish connection"

  Scenario: Save settings with only the host and the port specified
    Given uncheck "Use default" for "Encryption" field
    And uncheck "Use default" for "Username" field
    And uncheck "Use default" for "Password" field
    When I fill form with:
      | Encryption | None |
    And I click "Check Connection (New Settings)"
    Then I should see "Connection established successfully"
    When I save form
    Then I should see "Configuration saved" flash message
    And I should not see "Could not establish the SMTP connection"
    When I click "Check Connection (Saved Settings)"
    Then I should see "Connection established successfully"

  Scenario: Check connection with new settings without credentials to an unreachable host
    When I fill form with:
      | Host | smtp.unreachable.example.org |
    And I click "Check Connection (New Settings)"
    Then I should see "Could not establish connection"
