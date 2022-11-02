@regression
@ticket-BAP-18153
@fixture-OroLocaleBundle:LocalizationFixture.yml

Feature: Different localization in system configuration setting
  In order to be able to operate localizations in different system configurations
  As an administrator
  I set specific localizations for all configurations and check it

  Scenario: Change localization in system configuration
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [Localization1] |
      | Default Localization  | Localization1   |
    When I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Change localization in organization configuration
    Given I go to System/User Management/Organizations
    And I click Configuration "Oro" in grid
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use System" for "Default Localization" field
    And I fill form with:
      | Enabled Localizations | [Localization2] |
      | Default Localization  | Localization2   |
    When I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check localization in system configuration
    Given I go to System/Configuration
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    Then "Configuration Localization Form" must contains values:
      | Default Localization | Localization1 |

  Scenario: Check localization in organization configuration
    Given I go to System/User Management/Organizations
    And I click Configuration "Oro" in grid
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    Then "Configuration Localization Form" must contains values:
      | Default Localization | Localization2 |

  Scenario: Check localization in user configuration
    Given I click My Configuration in user menu
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    Then "Configuration Localization Form" must contains values:
      | Default Localization | Localization2 |
