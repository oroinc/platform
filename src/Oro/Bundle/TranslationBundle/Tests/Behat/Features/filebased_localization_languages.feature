@fixture-OroTranslationBundle:FileBasedLanguage.yml
Feature: File-Based localization languages
  In order to use the application with file-based languages
  As an Administrator
  I should be able to see such language in System Localization Languages and need to be sure it cannot be installed from Crowdin

  Scenario: Localization languages management
    Given I login as "admin" user
    When I go to System/Localization/Languages
    And I click "Add Language"
    And I fill form with:
      | Language | Estonian (Estonia) - et_EE |
    When I click "Add Language" in modal window
    Then I should see "Language has been added" flash message
    And I should see following grid:
      | Language                     | Status   | Updates          |
      | English                      | Enabled  |                  |
      | French (France) File Based   | Disabled |                  |
      | Estonian (Estonia)           | Disabled | Can be installed |
    And I should see following actions for English in grid:
      | Upload Translation File   |
      | Download Translation File |
    And I should see following actions for French (France) File Based in grid:
      | Enable                    |
      | Upload Translation File   |
      | Download Translation File |
    And I should see following actions for Estonian (Estonia) in grid:
      | Enable                    |
      | Upload Translation File   |
      | Download Translation File |
      | Install                   |
