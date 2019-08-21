@regression
@fixture-OroLocaleBundle:PortugueseLocalization.yml
Feature: User
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Create new user
    Given I login as administrator
    And I go to System/User Management/Users
    And I click "Create User"
    When I fill "User Form" with:
      | Username          | userName       |
      | Password          | Pa$$w0rd       |
      | Re-Enter Password | Pa$$w0rd       |
      | First Name        | First Name     |
      | Last Name         | Last Name      |
      | Primary Email     | email@test.com |
      | Roles             | Administrator  |
      | Enabled           | Enabled        |
    And I save and close form
    Then I should see "User saved" flash message

  Scenario: Create new user with generated password
    Given I go to System/User Management/Users
    And I click "Create User"
    When I fill "User Form" with:
      | Username          | userName1       |
      | First Name        | First Name1     |
      | Last Name         | Last Name1      |
      | Primary Email     | email1@test.com |
      | Roles             | Administrator   |
      | Birthday          | 1990-07-24      |
      | Enabled           | Enabled         |
      | Generate Password | true            |
    And I save and close form
    Then I should see "User saved" flash message
    And I should see user with:
      | Username | userName1         |
      | Emails   | email1@test.com   |
      | Roles    | Administrator     |
      | Birthday | Jul 24, 1990 (age |

  Scenario: Create new user with birthday field formatted in the Portuguese locale
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill "Configuration Localization Form" with:
      | Primary Location Use Default | false                 |
      | Primary Location             | Brazil                |
      | Enabled Localizations        | [English, Portuguese] |
      | Default Localization         | Portuguese            |
    And I click "Save settings"
    And I should see "Configuration saved" flash message
    And I go to System/User Management/Users
    And I click "Create User"
    When I fill "User Form" with:
      | Username          | userName2       |
      | Password          | Pa$$w0rd        |
      | Re-Enter Password | Pa$$w0rd        |
      | First Name        | First Name      |
      | Last Name         | Last Name       |
      | Primary Email     | email2@test.com |
      | Roles             | Administrator   |
      | Birthday          | 1990-07-24      |
      | Enabled           | Enabled         |
    And I save and close form
    Then I should see "User saved" flash message
    And I should see user with:
      | Username | userName2              |
      | Emails   | email2@test.com        |
      | Roles    | Administrator          |
      | Birthday | 24 de Jul de 1990 (age |
