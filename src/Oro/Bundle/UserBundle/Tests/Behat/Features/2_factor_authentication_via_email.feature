@not-automated
Feature: two-factor authentication via email
  In order to increase login security level
  As Administrator user
  I need to manage two-factor authentication using email

  Scenario: turn on two-factor authentication
    Given I login as "Administrator" user
    And I go to System/Configuration/User settings
    When I select "Always require the code" from "Two-factor authentication"
    And I fill in "Auto-expire verification code" with "10s"
    And I fill in "Max Login Attempts" with "3"
    And I save setting
    Then "Authentication code is required" page should be displayed after login

  Scenario: try to login with TFA
    Given I login as "Sales Rep" user
    When I submit credentials
    And I go to my mailbox
    And I pick up verification code
    And I go back to Oro side
    And I put verification code
    And I submit form
    Then I could see the Dashboard

  Scenario: try to login with TFA incorrect code
    Given I login as "Sales Rep" user
    When I submit credentials
    And I put "123" verification code
    And I submit form
    Then I am on Login page
    And I should see "Invalid credentials. You have 2 login attempts remaining." error message

  Scenario: try to login with TFA expired code
    Given I login as "Sales Rep" user
    When I submit credentials
    And I go to my mailbox
    And I pick up verification code
    And I go back to Oro side
    And I wait for 10 minutes
    And I put verification code
    And I submit form
    Then I am on Login page
    And I should see "Invalid credentials. You have 1 login attempts remaining." error message

  Scenario: try to login with TFA old code
    Given I login as "Sales Rep" user
    When I submit credentials
    And I go to my mailbox
    And I pick up verification code
    And I go back to Oro side
    And I wait for 10 minutes
    And I put verification code
    And I submit form
    Then I am on Login page
    And I should see "Account is locked. Please contact your administrator." error message

  Scenario: unlock user
    Given I login as "Administrator" user
    And I open "Sales Rep" user edit page
    And I select "Active" from "Status"
    When I save and close form
    Then "Sales Rep" user could login to the Dashboard

  Scenario: one-time TFA use
    Given: I login as "Administrator" user
    And I go to System/Configuration/User settings
    When I select "Remember the user after the first successful login" from "Two-factor authentication"
    Then "Authentication code is required" page should be displayed after login

  Scenario: try to login with one-time TFA
    Given: I login as "Sales Rep" user
    When I submit credentials
    And I go to my mailbox
    And I pick up verification code
    And I go back to Oro side
    And I put verification code
    And I submit form
    And I log out
    And I submit credentials
    Then I could see the Dashboard

  Scenario: try to login with one-time TFA from another browser
    Given: I login as "Sales Rep" user from another browser
    When I submit credentials
    Then Then "Authentication code is required" page should be displayed after login

  Scenario: turn off two-factor authentication
    Given I login as "Administrator" user
    And I go to System/Configuration/User settings
    When I select "not use TFA" from "Two-factor authentication"
    And I save setting
    And log out
    And I login as "Sales Rep" user
    Then I could see the Dashboard
