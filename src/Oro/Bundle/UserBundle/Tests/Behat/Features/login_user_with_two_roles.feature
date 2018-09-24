@ticket-OEE-1532
Feature: Login user with two roles
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

  Scenario: Creation of the First Role
    Given I login as administrator
    And go to System/ User Management/ Roles
    And I click "Create Role"
    And I fill form with:
      | Role | First Role |
    When I save and create new form
    Then I should see "Role saved" flash message

  Scenario: Creation of the Second Role
    And I fill form with:
      | Role | Second Role |
    When I save and close form
    Then I should see "Role saved" flash message

  Scenario: Create new User and assign only just created Roles
    Given go to System/ User Management/ Users
    And click "Create User"
    And I fill "User Form" with:
      | Username            | userName       |
      | Password            | Pa$$w0rd       |
      | Re-Enter Password   | Pa$$w0rd       |
      | First Name          | First Name     |
      | Last Name           | Last Name      |
      | Primary Email       | email@test.com |
      | OroCRM Organization | true           |
      | First Role          | true           |
      | Second Role         | true           |
      | Enabled             | Enabled        |
    When I save and close form
    Then I should see "User saved" flash message
    And I should see "First Role"
    And I should see "Second Role"

  Scenario: Successful login
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | userName |
      | Password | Pa$$w0rd |
    When I click "Log in"
    Then I should be on Admin Dashboard page
