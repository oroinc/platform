@ticket-BB-13983
@fixture-OroTranslationBundle:PluralTranslationsManagementFixture.yml

Feature: Plural translations management
  In order to correctly translate application to languages with multiple plural forms
  As an administrator
  I want to be able to set translations for phrases with multiple plural forms

  Scenario: Feature background
    Given I login as administrator
    And I go to System/Localization/Translations
    When I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Change language settings
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    When I fill "Language Settings System Config Form" with:
      | Supported languages  | Russian (Russia) |
      | Use Default Language | false            |
      | Default Language     | Russian (Russia) |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: I check that plural translations are displayed without errors in case when there are not all plural forms translated
    Given I go to System/User Management/Roles
    When I click on Administrator in grid
    Then I should see "9 записи (множ. число 1 форма)"
