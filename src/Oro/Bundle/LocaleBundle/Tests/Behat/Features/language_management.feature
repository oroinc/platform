@regression
@ticket-BAP-14163
@ticket-BB-12106
@automatically-ticket-tagged
Feature: Language management
  In order to manage available languages
  As Administrator
  I need to add new Languages and download translations for them

  Scenario: Add Germany language
    Given I login as administrator
    When I go to System/Localization/Languages
    Then I should see English in grid with following data:
      | Status | Enabled |
    When I click "Add Language"
    And I select "German (Germany) - de_DE" from "Language"
    And I click "Add Language" in modal window
    Then I should see "Language has been added" flash message
    And I should see "German (Germany)" in grid with following data:
      | Status  | Disabled         |
      | Updates | Can be installed |

  Scenario: Enable Germany language
    Given I go to System/Localization/Languages
    When I click Enable "German (Germany)" in grid
    Then I should see "Language has been enabled" flash message
    And I should see German in grid with following data:
      | Status  | Enabled          |
      | Updates | Can be installed |

  Scenario: Install translations for Germany language from Crowdin
    Given I go to System/Localization/Languages
    When I click Install "German (Germany)" in grid
    Then I should see "UiDialog" with elements:
      | Title    | Install "German (Germany)" language |
      | okButton | Install                             |

    When I click "Install"
    Then I should see "German (Germany)" in grid with following data:
      | Status  | Enabled    |
      | Updates | Up to date |

  Scenario: Check installed translations for Germany language
    Given I go to System/Localization/Translations
    When I check "English" in Language filter
    And I keep in mind number of records in list
    And I reset Language filter
    And I check "German (Germany)" in Language filter
    Then the number of records remained the same
    When I check "Yes" in "Translated: All" filter strictly
    Then the number of records greater than or equal to 1

  Scenario: Create localization with German language
    Given I go to System/Localization/Localizations
    When I click "Create Localization"
    And I fill "Localization Form" with:
      | Name       | German Localization Name  |
      | Title      | German Localization Title |
      | Language   | German (Germany)          |
      | Formatting | German (Germany)          |
    And I save and close form
    Then I should see "Localization has been saved" flash message
    And I should see Localization with:
      | Name       | German Localization Name  |
      | Title      | German Localization Title |
      | Language   | German (Germany)          |
      | Formatting | German (Germany)          |
