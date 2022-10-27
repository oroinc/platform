Feature: Microsoft Azure Application configuration available
  In order to allow integration with Microsoft Azure application
  As an administrator
  I want to see be able to configure Microsoft Azure Application configurations

  Scenario: Saving Microsoft Azure Application Configuration
    Given I login as administrator
    When I go to System/ Configuration
    And I follow "System Configuration/Integrations/Microsoft Settings" on configuration sidebar
    And I should see "Redirect URI"
    And the "Enable Emails Sync" field should be disabled
    And the "Enable Calendar Sync" field should be disabled
    And the "Enable Tasks Sync" field should be disabled
    And uncheck "Use default" for "Client Secret" field
    And I fill form with:
      | Application (client) ID   | 12345         |
      | Client Secret             | 12345         |
      | Directory (tenant) ID     | SampleTenant  |
    And I save setting
    Then I should see "Configuration saved" flash message
    And the "Enable Emails Sync" field should be enabled
    And the "Enable Calendar Sync" field should be enabled
    And the "Enable Tasks Sync" field should be enabled

  Scenario: Saving empty Microsoft Integration configuration should disable Email, Calendar, Tasks checkboxes
    When I fill form with:
      | Application (client) ID   | |
      | Client Secret             | |
      | Directory (tenant) ID     | |
    And check "Use default" for "Client Secret" field
    And I save setting
    Then I should see "Configuration saved" flash message
    And the "Enable Emails Sync" field should be disabled
    And the "Enable Calendar Sync" field should be disabled
    And the "Enable Tasks Sync" field should be disabled

  Scenario: Saving Microsoft Integration configuration with its checked checkboxes values should save checkboxes values as well
    When uncheck "Use default" for "Client Secret" field
    And I fill form with:
      | Application (client) ID   | 12345         |
      | Client Secret             | 12345         |
      | Directory (tenant) ID     | SampleTenant  |
    And I check "Enable Emails Sync"
    And I check "Enable Calendar Sync"
    And I check "Enable Tasks Sync"
    And I save setting
    Then I should see "Configuration saved" flash message
    When I reload the page
    Then the "Application (client) ID" field should contain "12345"
    Then the "Client Secret" field should contain "*****"
    And the "Directory (tenant) ID" field should contain "SampleTenant"
    And the "Enable Emails Sync" checkbox should be checked
    And the "Enable Calendar Sync" checkbox should be checked
    And the "Enable Tasks Sync" checkbox should be checked

