@ticket-BAP-19446
@fixture-OroDataGridBundle:all_grid_view_label.yml

Feature: All Grid View Label
  In order to correctly translate application to different languages
  As an Administrator
  I want to be able to set translation for All grid view label

  Scenario: Check All grid view label
    Given I login as administrator
    When I go to System/User Management/Groups
    Then I should see "All Groups"

  Scenario: Change language settings
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Enabled Localizations | [Zulu_Loc] |
      | Default Localization  | Zulu_Loc   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check All grid view label is changed
    And I go to System/User Management/Groups
    Then I should see "All ZuluGroups"
