# features/user.feature
Feature: User
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user
Scenario: Create new user
  Given Login as an existing "admin" user and "admin" password
  And I go to "/user/create"
  When I fill "User Form" with:
        | username          | userName       |
        | password          | 123123q        |
        | re-enter password | 123123q        |
        | first name        | First Name     |
        | last name         | Last Name      |
        | email             | email@test.com |
  And I select "Active" from "Status"
  And I check "Manager"
  And I press "Save and Close"
  And the url should match "/user/view/\d+"
  Then I should see "User saved"
