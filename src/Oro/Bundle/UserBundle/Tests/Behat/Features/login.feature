Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

Scenario: Success login
  Given I am on "/user/login"
  And I fill "Login" form with:
      | label    | value |
      | Username | admin |
      | Password | admin |
  And I press "Log in"
  And I should be on "/"

Scenario: Fail login
  Given I am on "/user/login"
  And I fill "Login" form with:
      | label    | value |
      | Username | user |
      | Password | pass |
  And I press "Log in"
  And I should be on "/user/login"
  And I should see "Invalid user name or password."
