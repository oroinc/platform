@regression
@ticket-BB-16591
Feature: User
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create new user
    Given I operate as the Admin
    And I login as administrator
    And I go to System/User Management/Users
    And I click "Create User"
    When fill "Create User Form" with:
      | Username            | user1Name      |
      | Password            | user1Name      |
      | Re-Enter Password   | user1Name      |
      | First Name          | First Name     |
      | Last Name           | Last Name      |
      | Primary Email       | email@test.com |
      | Roles               | Administrator  |
      | Enabled             | Enabled        |
      | OroCRM Organization | true           |
    And I save and close form
    Then I should see "User saved" flash message

  Scenario: Create new user with generated password
    Given I go to System/User Management/Users
    And I click "Create User"
    And fill "Create User Form" with:
      | Username            | userName1       |
      | First Name          | First Name1     |
      | Last Name           | Last Name1      |
      | Primary Email       | email1@test.com |
      | Roles               | Administrator   |
      | Enabled             | Enabled         |
      | Generate Password   | true            |
      | OroCRM Organization | true            |

  @skipWait
  Scenario: Follow email link
    Given I save and close form
    Then I should see "User saved" flash message
    And I operate as the User
    And I follow "RESET PASSWORD" link from the email
    And fill "User Reset Password Form" with:
      | New password    | userName1 |
      | Repeat password | userName1 |
    And click "Reset"
    Then I should see "Your password was successfully reset. You may log in now."

  Scenario: Login as new user with generated password
    Given I login as "userName1" user
    Then should see "Dashboard"
    And click logout in user menu

  Scenario: Login as new user with manually created password
    Given I login as "user1Name" user
    Then should see "Dashboard"
