Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

Scenario: Success login
  Given I open "Login" page
  And I fill "Login Form" with:
      | Username | admin |
      | Password | admin |
  And I press "Log in"
  And I should be on "Home" page

Scenario: Fail login
  Given I open "Login" page
  And I fill "Login Form" with:
      | Username | user |
      | Password | pass |
  And I press "Log in"
  And I should be on "Login" page
  And I should see "Invalid user name or password."
