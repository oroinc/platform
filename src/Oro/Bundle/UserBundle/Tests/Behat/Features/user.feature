# features/user.feature
Feature: User
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user
Scenario: Create new user
  Given I login as "admin" user with "admin" password
  And go to System/ User Management/ Users
  And press "Create User"
  When I fill "User" form with:
        | Username          | userName       |
        | Password          | 123123q        |
        | Re-Enter Password | 123123q        |
        | First Name        | First Name     |
        | Last Name         | Last Name      |
        | Primary Email     | email@test.com |
  And I select "Active" from "Status"
  And I check "Sales Rep"
  And I press "Save and Close"
  Then I should see "User saved" flash message
