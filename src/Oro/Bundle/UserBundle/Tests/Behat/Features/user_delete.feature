@ticket-BAP-20913

Feature: User delete
  In order to delete users
  As a OroCRM Admin user
  I need to be able to see first user in a grid after some user was delete

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |

  Scenario: Create new users
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
    And I should see "User saved" flash message
    And I go to System/User Management/Users
    And I click "Create User"
    And fill "Create User Form" with:
      | Username            | user2Name      |
      | Password            | user2Name      |
      | Re-Enter Password   | user2Name      |
      | First Name          | First Name     |
      | Last Name           | Last Name      |
      | Primary Email       | email2@test.com |
      | Roles               | Administrator  |
      | Enabled             | Enabled        |
      | OroCRM Organization | true           |
    And I save and close form
    Then I should see "User saved" flash message

  Scenario: Check grid after row deletion
    When I go to System/User Management/Users
    And I check "MassActionCheckbox" element
    And click delete "user2Name" in grid
    And I should see "Are you sure you want to delete this item?"
    And I click "Yes, Delete"
    And I should not see "user2Name"
    And click View "admin" in grid
    Then I should see "Users / John Doe"
