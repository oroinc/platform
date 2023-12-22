@regression
@ticket-BAP-22316
@fixture-OroLocaleBundle:LocalizationFixture.yml

Feature: Email template editor uses localization names in editor tabs
  In order to be able to differentiate localizations that have the same title when editing email templates
  As an administrator
  I want to see localization names (unique) in editor tabs (instead of non-unique localization titles)

  Scenario: Verify that email template editor uses localization titles by default
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    Then I should see "Localization 1"
    And I should see "Localization 2"

  Scenario: Change system configuration setting to use localization names in email template editor tabs
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use default" for "Use Localization Names in Email Template Editor" field
    And I fill form with:
      | Use Localization Names in Email Template Editor | true |
    When I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Verify that email template editor uses localization names instead of localization titles
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    Then I should not see "Localization 1"
    And I should not see "Localization 2"
    But I should see "Localization1"
    And I should see "Localization2"
