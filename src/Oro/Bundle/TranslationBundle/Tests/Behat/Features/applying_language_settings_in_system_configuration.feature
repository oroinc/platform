@ticket-BAP-13139
@automatically-ticket-tagged
@fixture-OroUserBundle:user.yml
@fixture-OroTranslationBundle:LanguageFixture.yml
Feature: Applying language settings in system configuration
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Reset user language settings when language no more allowed
    Given I login as "admin" user
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I fill "Language Settings System Config Form" with:
      | Supported languages | German |
    And I save form

    When I login as "charlie" user
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I uncheck "Use Organization"
    And I fill "Language Settings System Config Form" with:
      | Default Language     | German |
      | Use Default Language | true   |
    And I save form
    Then I should see "German"

    When I login as "admin" user
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I fill "Language Settings System Config Form" with:
      | Supported languages | English |
    And I save form

    When I login as "charlie" user
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    Then I should see "English"
