@ticket-BB-14072
Feature: Check that user with role without permissions does not have irrelevant access
  In order to ensure that user without permissions does not have irrelevant access
  As a user without permissions
  I should not see navigation menu items

  Scenario: Creation of the Role without permissions
    Given I login as administrator
    And go to System/ User Management/ Roles
    And I click "Create Role"
    And I fill form with:
      | Role | Bare Role |
    When I save and create new form
    Then I should see "Role saved" flash message

  Scenario: Create new User and assign only Role without permissions
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
      | Bare Role           | true           |
      | Enabled             | Enabled        |
    When I save and close form
    Then I should see "User saved" flash message
    And I should see "Bare Role"

  Scenario: Successful login as new User
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | userName |
      | Password | Pa$$w0rd |
    When I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Check that user without permissions does not see standard navigation menu item
    Given I should not see "Customers" in the "MainMenu" element
    And I should not see "Sales" in the "MainMenu" element
    And I should not see "Products" in the "MainMenu" element
    And I should not see "Marketing" in the "MainMenu" element
    And I should not see "Taxes" in the "MainMenu" element
    And I should not see "Inventory" in the "MainMenu" element
    And I should not see "Activities" in the "MainMenu" element
    And I should not see "Reports & Segments" in the "MainMenu" element
    And I should not see "System" in the "MainMenu" element
