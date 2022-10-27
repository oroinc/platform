@ticket-BB-13983
@fixture-OroTranslationBundle:PluralTranslationsManagementFixture.yml

Feature: Plural translations management
  In order to correctly translate application to languages with multiple plural forms
  As an administrator
  I want to be able to set translations for phrases with multiple plural forms

  Scenario: Feature background
    Given I login as administrator

  Scenario: Change language settings
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Enabled Localizations | Russian (Russia) |
      | Default Localization  | Russian (Russia) |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: I check that plural translations are displayed without errors in case when there are not all plural forms translated
    Given I go to System/User Management/Roles
    When I click on Administrator in grid
    Then I should see "10 записей (множ. число 2 форма)"
