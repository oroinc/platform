@ticket-BAP-15478
Feature: Show tooltips in configuration
  In order to control system display behavior
  As Administrator
  I need to be toogle tooltips

  Scenario: Open google integration header tooltip
    Given I login as administrator
    And go to System/ Configuration
    And I follow "System Configuration/Integrations/Google Settings" on configuration sidebar
    And I click "Google Integration Settings Tooltip Icon"
    And I should see "Please read instructions for obtaining credentials. Make sure that your OroCRM domain is included into `Authorized JavaScript origins` and `Authorized redirect URIs`."
