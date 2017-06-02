Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

  Scenario: Success login
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin |
      | Password | admin |
    When I press "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Fail login
    Given I am logged out
    When I login as "user" user
    Then I should see "Invalid user name or password."
