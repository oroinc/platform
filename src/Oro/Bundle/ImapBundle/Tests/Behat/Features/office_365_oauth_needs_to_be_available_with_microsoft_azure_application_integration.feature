Feature: Office 365 OAuth needs to be available with Microsoft Azure Application integration
  In order to allow OAuth for Office 365 clients
  As an administrator
  I want to be able to turn on/off the switch for OAuth on Microsoft Integration configuration screen

  Scenario: As administrator I can see enable OAuth switch in
    Microsoft Integration Configuration
    Given I login as administrator
    And I go to System/ Configuration
    And I follow "System Configuration/Integrations/Microsoft Settings" on configuration sidebar
    Then I should see "Microsoft 365 Integrations"
    And I should see "Enable Emails Sync"
