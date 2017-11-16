@regression
Feature: Mobile menu
  In order to simplify navigation on smaller device screens and touch screen devices
  As a Customer
  I want to see the menu expanced full-screen with large, easily clickable items.

  Scenario: Change theme to Custom
    Given here is the "Admin" under "first_session"
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And fill "Theme Form" with:
      | ThemeUseDefault | false        |
      | Theme           | Custom theme |
    And submit form

  Scenario: Check that search option places in fullscreen popup in custom theme
    Given here is the "User" under "320_session"
    And I set window size to 320x640
    When I am on homepage
    Then I should not see an "Search Form" element
    And I should not see an "Main Menu Search Button" element
    When I click "Main Menu Button"
    Then I should see an "Fullscreen Popup" element
    And I should see an "Search Form" element
