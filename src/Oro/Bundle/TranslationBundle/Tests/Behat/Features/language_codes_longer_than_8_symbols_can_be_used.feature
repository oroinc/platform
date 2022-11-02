@ticket-BAP-20410
@regression

Feature: Language codes longer than 8 symbols can be used
  In order to use the application with different languages
  As an administrator
  I should be able to add languages with codes longer than 8 symbols

  Scenario: I should be able to add language with a code longer than 8 symbols
    Given I login as administrator
    And I go to System/Localization/Languages
    And I click "Add Language"
    And I fill form with:
      | Language | Azerbaijani (Latin, Azerbaijan) - az_Latn_AZ |
    When I click "Add Language" in modal window
    Then I should see "Language has been added" flash message
    When I click Enable "Azerbaijani (Latin, Azerbaijan)" in grid
    Then I should see "Language has been enabled" flash message

  Scenario: I should be able to enable language with a code longer than 8 symbols
    Given I go to System/Localization/Localizations
    And I click "Create Localization"
    And fill "Localization Form" with:
     | Name       | Azerbaijani                     |
     | Title      | Azerbaijani                     |
     | Language   | Azerbaijani (Latin, Azerbaijan) |
     | Formatting | Azerbaijani                     |
    When I save form
    Then I should see "Localization has been saved" flash message
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, Azerbaijani] |
      | Default Localization  | Azerbaijani            |
    And I submit form
    Then I should not see "500. Internal Server Error"
    And I should see "Configuration saved" flash message
