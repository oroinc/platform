@ticket-BB-14685
@fixture-OroUserBundle:user.yml

Feature: User Case Insensitive Usernames
  In order to avoid possible user mistakes
  As an Administrator
  I must not be able to login with username in another case

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Check successful login with original username
    Given I proceed as the User
    And I am on Login page
    And I fill form with:
      | Username | charlie |
      | Password | charlie |
    When I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Check user create is not allowed when same username in lowercase exists
    Given I proceed as the Admin
    And I login as administrator
    And go to System/User Management/Users
    And click "Create User"
    When I fill "User Form" with:
      | Username          | Charlie                    |
      | Password          | Pa$$w0rd                   |
      | Re-Enter Password | Pa$$w0rd                   |
      | First Name        | Charlie                    |
      | Last Name         | Sheen                      |
      | Primary Email     | second_charlie@example.com |
      | Roles             | Administrator              |
      | Enabled           | Enabled                    |
    And I save and close form
    Then I should see "This username is already registered by another user. Please provide unique username."
    And I click "Cancel"

  Scenario: Check user update is not allowed when same username in lowercase exists
    Given I go to System/User Management/Users
    And I filter Username as is equal to "charlie"
    And I click Edit charlie in grid
    When I fill "User Form" with:
      | Username | ADMIN |
    And I save and close form
    Then I should see "This username is already registered by another user. Please provide unique username."
    And I click "Cancel"
