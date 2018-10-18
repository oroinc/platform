@regression
@ticket-BAP-14021
@ticket-BAP-15576
@automatically-ticket-tagged
@fixture-OroLocaleBundle:japanese-language.yml
Feature: Localization Crud
  In order to manage available localizations
  As Administrator
  I need to add new Localizations and enable Localizations at System Configuration

  Scenario: Check default localization
    Given I login as administrator
    When I go to System/Localization/Localizations
    Then I should see "English" in grid with following data:
      | Title               | English |
      | Parent localization | N/A     |

  Scenario: Verify HTML tags
    Given I click "Create Localization"
    And I click "Fallback Status"
    When I fill "Localization Create Form" with:
      | Name                | Test <script>alert(1)</script>        |
      | Title Use           | false                                 |
      | Title Default Value | TestDefault <script>alert(1)</script> |
      | Title English       | TestEnglish <script>alert(1)</script> |
      | Language            | Japanese (Japan)                      |
      | Formatting          | Japanese (Japan)                      |
    And I save form
    And I click "Fallback Status"
    Then "Localization Create Form" must contains values:
      | Name                | Test alert(1)        |
      | Title Default Value | TestDefault alert(1) |
      | Title English       | TestEnglish alert(1) |
    When I save and close form
    Then I should see "Test alert(1)"
    And I should see "TestDefault alert(1)"
    And go to System/Localization/Localizations
    Then I should see "Test alert(1)" in grid with following data:
      | Title | TestDefault alert(1) |

  Scenario: Check required fields
    Given I click "Create Localization"
    When I save form
    Then I should see validation errors:
      | Name | This value should not be blank. |
      | Language | This value should not be blank.      |
      | Formatting | This value should not be blank.    |

  Scenario: Create new localization
    Given I click "Fallback Status"
    When I fill "Localization Create Form" with:
      | Name                | Japanese         |
      | Title Use           | false            |
      | Title Default Value | Jap            |
      | Title English       | Japanese         |
      | Language            | Japanese (Japan) |
      | Formatting          | Japanese (Japan) |
    And I save and close form
    And go to System/Localization/Localizations
    Then I should see "Japanese" in grid with following data:
      | Title               | Jap    |
      | Parent localization | N/A    |
      | Language            | Japanese (Japan) - ja_JP |
      | Formatting          | Japanese (Japan) - ja_JP |

  Scenario: Edit existing localization
    Given I click Edit Japanese in grid
    And click "Fallback Status"
    When I fill "Localization Create Form" with:
      | Name                | Dutch         |
      | Title Use           | false            |
      | Title Default Value | Netherlands              |
      | Title English       | NL         |
      | Language            | Dutch (Netherlands) |
      | Formatting          | Dutch (Netherlands) |
    And I save and close form
    And go to System/Localization/Localizations
    Then I should see "Dutch" in grid with following data:
      | Title               | Netherlands    |
      | Parent localization | N/A    |
      | Language            | Dutch (Netherlands) - nl_NL |
      | Formatting          | Dutch (Netherlands) - nl_NL |
    And there are 3 records in grid

  Scenario: Change Localization settings
    Given I should see following actions for Dutch in grid:
      | View   |
      | Edit   |
      | Delete |
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill "System Config Form" with:
      | Enabled Localizations | Dutch |
      | Default Localization  | Dutch |
    And save form
    And go to System/Localization/Localizations
    Then I should see following actions for English in grid:
      | View   |
      | Edit   |
      | Delete |

  Scenario: Delete new localization but default can't be removed
    Given I click Delete English in grid
    When I confirm deletion
    Then there is two record in grid
    And there is no "English" in grid
    And I should see Dutch in grid
    But I should not see following actions for Dutch in grid:
      | Delete |
