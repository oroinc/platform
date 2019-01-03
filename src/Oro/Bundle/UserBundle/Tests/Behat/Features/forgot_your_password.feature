@ticket-BB-14229
@fixture-OroUserBundle:user.yml

Feature: Forgot your password
  In order to restore password
  As a User
  I want to have the forgot password functionality

  Scenario: Verify not existing email address
    Given I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | nonexisting@example.com |
    When I confirm reset password
    Then I should see "If there is a user account associated with ...@example.com you will receive an email with a link to reset your password."

  Scenario: Verify not existing username
    Given I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | nonexisting |
    When I confirm reset password
    Then I should see "If there is a user account associated with nonexisting you will receive an email with a link to reset your password."

  Scenario: Verify recovery message by email address
    Given I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | admin@example.com |
    When I confirm reset password
    Then I should see "If there is a user account associated with ...@example.com you will receive an email with a link to reset your password."

  Scenario: Verify recovery message by username
    Given I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | charlie |
    When I confirm reset password
    Then I should see "If there is a user account associated with ...@example.com you will receive an email with a link to reset your password."
