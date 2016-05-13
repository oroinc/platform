Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

Scenario: Success login
  Given I am on "/user/login"
  And I fill "Login" form with:
      | Username | admin |
      | Password | admin |
  And I press "Log in"
  And I should be on "/"

Scenario Outline: Fail login
  Given I am on "/user/login"
  And I fill "Login" form with:
      | Username | <login>    |
      | Password | <password> |
  And I press "Log in"
  And I should be on "/user/login"
  And I should see "Invalid user name or password."

  Examples:
  | login | password |
  | user  | pass     |
  | user2 | pass2    |
