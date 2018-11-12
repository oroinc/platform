Feature: User CRUD
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Create new user
    Given I login as administrator
    And go to System/User Management/Users
    And click "Create User"
    When I save and close form
    Then I should see validation errors:
      | Enabled       | This value should not be null.  |
      | Username      | This value should not be blank. |
      | Password      | This value should not be blank. |
      | First Name    | This value should not be blank. |
      | Last Name     | This value should not be blank. |
      | Primary Email | This value should not be blank. |
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

  Scenario: Edit user data
    Given I go to System/User Management/Users
    And click Edit First Name in grid
    When I fill form with:
      | Username          | johnny                |
      | First Name        | Johnny                |
      | Last Name         | Mnemonic              |
      | Primary Email     | edited@test.com       |
    And I save and close form
    Then I should see "User saved" flash message
    When I go to System/User Management/Users
    Then I should see johnny in grid with following data:
      | Username          | johnny                |
      | First Name        | Johnny                |
      | Last Name         | Mnemonic              |
      | Primary Email     | edited@test.com       |

  Scenario: Delete user
    Given I should see johnny in grid
    And I keep in mind number of records in list
    When I click Delete johnny in grid
    And I confirm deletion
    Then the number of records decreased by 1
    And I should not see "johnny"
