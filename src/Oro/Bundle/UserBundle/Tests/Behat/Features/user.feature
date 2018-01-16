@regression
Feature: User
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Create new user
    Given I login as administrator
    And I go to System/User Management/Users
    And I click "Create User"
    When I fill form with:
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
    When I fill form with:
      | Username           | userName1       |
      | First Name         | First Name1     |
      | Last Name          | Last Name1      |
      | Primary Email      | email1@test.com |
      | Roles              | Administrator   |
      | Enabled            | Enabled         |
      | Generate Password  | true            |
    And I save and close form
    Then I should see "User saved" flash message
