@fixture-OroUserBundle:user.yml

Feature: User back office login
  In order to manage access
  As an application administrator
  I want to be sure that "Login" functionality is working fine

  Scenario: successful back office login using username
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin |
      | Password | admin |
    When I press "Log in"
    Then I should be on Admin Dashboard page

  Scenario: failing back office login using username with a wrong password
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin |
      | Password | admim |
    When I press "Log in"
    Then I should see "Invalid credentials. You have 9 login attempts remaining."
    # Then I should see "Invalid user name or password." /BAP-15864

  Scenario: successful back office login using email
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | charlie@example.com |
      | Password | charlie |
    When I press "Log in"
    Then I should be on Admin Dashboard page

  Scenario: failing back office login using email with a wrong password
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | charlie |
      | Password | charlie@example.com |
    When I press "Log in"
    Then I should see "Invalid credentials. You have 9 login attempts remaining."
    # Then I should see "Invalid user name or password." /BAP-15864

  Scenario: failing back office login of nonexistent user
    Given I am logged out
    And I am on Login page
    When I login as "user" user
    Then I should see "Invalid user name or password."

  Scenario: Check that username field has been filled up
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username |  |
      | Password | admin |
    When I press "Log in"
    Then  I am on Login page

  Scenario: Check that password field has been filled up
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin |
      | Password |  |
    When I press "Log in"
    Then  I am on Login page

  Scenario: Check that username and password fields have been filled up
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username |  |
      | Password |  |
    When I press "Log in"
    Then  I am on Login page

  Scenario: Redirect already logged user
    Given I am logged out
    And I login as "admin" user
    And I should be on Admin Dashboard page
    When I am on Login page
    Then I should be on Admin Dashboard page

  Scenario: back office user logout
    Given I am logged out
    And I login as "admin" user
    When I click Logout in user menu
    Then I am on Login page
