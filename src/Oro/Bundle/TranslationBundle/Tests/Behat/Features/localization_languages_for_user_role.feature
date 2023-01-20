@ticket-BAP-21510
@fixture-OroUserBundle:manager.yml
@fixture-OroTranslationBundle:LanguageUserRoleFixture.yml

Feature: Localization languages for user role
  In order to use the application with different languages
  As an Administrator
  I want to see these language codes display correctly

  Scenario: Edit role ROLE_TRANSLATOR
    Given I login as administrator
    When go to System/User Management/Roles
    And click "Create Role"
    Then fill form with:
      | Role | ROLE_TRANSLATOR |
    And select following permissions:
      | Language | View:Organization | Create:Organization | Edit:Organization | Translate:Organization |
    And I check "Export Entity Records" entity permission
    And I check "Import Entity Records" entity permission
    When I save and close form
    And I should see "Role saved" flash message

  Scenario: Changing the role of a user
    When go to System/User Management/Users
    And I click "Edit" on row "ethan@example.com" in grid
    Then I fill "User Form" with:
      | Roles | ROLE_TRANSLATOR |
    And I save and close form
    And I should see "User saved" flash message
    And click logout in user menu

  Scenario: Localization languages management
    When I login as "ethan" user
    And I go to System/Localization/Languages
    Then I should see following grid containing rows:
      | Language                | Status   | Updates          |
      | English (Canada)        | Disabled |                  |
      | French (France)         | Enabled  | Can be installed |
      | English (United States) | Enabled  | Can be installed |
    And I should see following actions for English (Canada) in grid:
      | Enable                    |
      | Upload Translation File   |
      | Download Translation File |
    And I should see following actions for French (France) in grid:
      | Disable                   |
      | Upload Translation File   |
      | Download Translation File |
      | Install                   |
    And I should see following actions for English (United States)) in grid:
      | Disable                   |
      | Upload Translation File   |
      | Download Translation File |
      | Install                   |
    And there are 4 records in grid
