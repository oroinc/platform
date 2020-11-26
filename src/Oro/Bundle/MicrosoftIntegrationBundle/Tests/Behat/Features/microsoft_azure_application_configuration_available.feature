Feature: Microsoft Azure Application configuration available
  In order to allow integration with Microsoft Azure application
  As an administrator
  I want to see be able to configure Microsoft Azure Application configurations

  Scenario: Saving Microsoft Azure Application Configuration
    Given I login as administrator
    When I go to System/ Configuration
    And I follow "System Configuration/Integrations/Microsoft Settings" on configuration sidebar
    And I should see "Redirect URI"
    And uncheck "Use default" for "Client Secret" field
    And I fill form with:
      | Application (client) ID   | 12345         |
      | Client Secret             | 12345         |
      | Directory (tenant) ID     | SampleTenant  |
    And I save setting
    Then I should see "Configuration saved" flash message
