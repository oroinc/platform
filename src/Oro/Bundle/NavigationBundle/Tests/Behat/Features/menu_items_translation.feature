@regression
@ticket-BB-15765

Feature: Menu Items Translation
  In order to support multi lingual setups
  As an Administrator
  I want to be able to create and use menu items in different languages

  Scenario: Create different window session
    Given sessions active:
      | Session1 | first_session  |
      | Session2 | second_session |

  Scenario: Add German language
    Given I proceed as the Session1
    And I login as administrator
    When I go to System/Localization/Languages
    Then I should see English in grid with following data:
      | Status | Enabled |
    When I click "Add Language"
    And I select "German (Germany) - de_DE" from "Language"
    And I click "Add Language" in modal window
    Then I should see "Language has been added" flash message
    When I click Enable "German (Germany)" in grid
    Then I should see "Language has been enabled" flash message
    When I click Install "German (Germany)" in grid
    And I click "Install" in modal window
    Then I should see "German (Germany)" in grid with following data:
      | Status  | Enabled    |
      | Updates | Up to date |

  Scenario: Create localization with German language
    Given I go to System/Localization/Localizations
    When I click "Create Localization"
    And I fill "Localization Form" with:
      | Name       | German           |
      | Title      | German           |
      | Language   | German (Germany) |
      | Formatting | German (Germany) |
    And I save and close form
    Then I should see "Localization has been saved" flash message

  Scenario: Enable German localization
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, German]       |
      | Default Localization  | English (United States) |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Create translatable menu item
    Given I go to System/Menus
    When click view "application_menu" in grid
    And I Create Menu Item
    And I click "Menu Form Title Fallbacks"
    And I fill "Menu Form" with:
      | Title                     | MItem EN |
      | Title Second Use Fallback | false    |
      | Title Second              | MItem DE |
      | URI                       | /        |
      | Icon                      | bolt     |
    And I save form
    And I reload the page
    Then I should see "MItem EN" in the "MainMenu" element

  Scenario: Change Default Localization
    Given I proceed as the Session2
    And I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Default Localization | German |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check German language applied for menu item
    Given I proceed as the Session1
    And I reload the page
    Then I should see "MItem DE" in the "MainMenu" element
