Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

Scenario: Success login
  Given I am on Login page
  And I fill "Login Form" with:
    | Username | admin |
    | Password | admin |
  And I press "Log in"
  And I should be on Admin Dashboard page

Scenario: Fail login
  Given I login as "user" user
  And I should see "Invalid user name or password."
