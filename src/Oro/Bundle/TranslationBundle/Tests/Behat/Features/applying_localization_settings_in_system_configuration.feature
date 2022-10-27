@ticket-BAP-13139
@automatically-ticket-tagged
@fixture-OroUserBundle:user.yml
@fixture-OroLocaleBundle:LocalizationFixture.yml
Feature: Applying localization settings in system configuration
  In order to have ability to change the UI language
  As an Administrator
  I need to be able to change localization in the System configuration for System and User levels hierarchically

  Scenario: Reset user localization settings when localization no more allowed
    Given I login as "admin" user
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | German Localization |
      | Default Localization  | German Localization |
    And I save form

    When I login as "charlie" user
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | German Localization |
    And I save form
    Then I should see "German Localization"

    When I login as "admin" user
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | English |
      | Default Localization  | English |
    And I save form

    When I login as "charlie" user
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    Then I should see "English"
