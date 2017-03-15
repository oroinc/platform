Feature: Language management
  In order to manage available languages
  As Administrator
  I need to add new Languages and download translations for them

  Scenario: Add Germany language
    Given I login as administrator
    When go to System/Localization/Languages
    Then should see English in grid with following data:
      | Status | Enabled |
    When click "Add Language"
    And select "German (Germany) - de_DE" from "Language"
    And click "Add Language"
    Then should see "Language has been added" flash message
    And should see "German (Germany)" in grid with following data:
      | Status  | Disabled         |
      | Updates | Can be installed |

  Scenario: Enable Germany language
    Given I login as administrator
    When go to System/Localization/Languages
    And click Enable "German (Germany)" in grid
    Then should see "Language has been enabled" flash message
    And should see German in grid with following data:
      | Status  | Enabled          |
      | Updates | Can be installed |

  Scenario: Install translations for Germany language from Crowdin
    Given I login as administrator
    When go to System/Localization/Languages
    And click Install "German (Germany)" in grid
    And press "Install"
    And should see "German (Germany)" in grid with following data:
      | Status  | Enabled    |
      | Updates | Up to date |

  Scenario: Check installed translations for Germany language
    Given I login as administrator
    When go to System/Localization/Translations
    And check "English" in Language filter
    And keep in mind number of records in list
    And reset Language filter
    And check "German (Germany)" in Language filter
    Then the number of records remained the same
    When check "Yes" in "Translated: All" filter strictly
    Then the number of records greater than or equal to 1

  Scenario: Create localization with German language
    Given I login as administrator
    When go to System/Localization/Localizations
    And press "Create Localization"
    And fill "Localization Form" with:
      | Name       | German Localization Name  |
      | Title      | German Localization Title |
      | Language   | German (Germany)          |
      | Formatting | German (Germany)          |
    And save and close form
    Then should see "Localization has been saved" flash message
    And should see Localization with:
      | Name       | German Localization Name  |
      | Title      | German Localization Title |
      | Language   | German (Germany)          |
      | Formatting | German (Germany)          |
