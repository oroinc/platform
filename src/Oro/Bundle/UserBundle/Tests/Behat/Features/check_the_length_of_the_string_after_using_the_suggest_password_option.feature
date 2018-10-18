@regression
@ticket-BAP-16494
Feature: Check the length of the string after using the "Suggest password" option
  In order to check Make sure that the length of the string always meets the requirements
  As a  Admin user
  I need to be able change password

  Scenario: Suggest password
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
    And I go to System/User Management/Users
    And I click view email@test.com in grid
    And follow "More actions"
    And follow "Change password"
    And I click "Suggest password"
    And I should not see "The password must be at least 8 characters long"
    And I click "Save"
    And I should see "Reset password email has been sent to user" flash message
    And Email should contains the following "Your password was successfully reset." text
