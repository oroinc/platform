@ticket-BB-21107
@fixture-OroLocaleBundle:LocalizationFixture.yml

Feature: Translation Message Sanitization
  In order to have safe translation messages
  As an Administrator
  I want to see that translation messages are sanitized

  Scenario: Feature Background
    Given I login as administrator
    And I reload the page

  Scenario: Specify unsafe translation
    Given I go to System/Localization/Translations
    And I check "English" in Language filter
    And I filter Key as is equal to "oro.user.group.owner.label"
    And I edit first record from grid:
      | Translated Value | <div data-role>Owner</div> |

  Scenario: Check Owner field label is sanitized
    When I go to System/ User Management/ Groups
    And I click "Create Group"
    Then I should see "<div>Owner</div>"
    And I should not see "<div data-role>Owner</div>"

  Scenario: Enable German localization
    Given go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Enabled Localizations | [English (United States), German Localization] |
      | Default Localization  | German Localization                            |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check Owner field label is sanitized in German Localization
    When I go to System/ User Management/ Groups
    And I click "Create Group"
    Then I should see "<div>Owner</div>"
    And I should not see "<div data-role>Owner</div>"
