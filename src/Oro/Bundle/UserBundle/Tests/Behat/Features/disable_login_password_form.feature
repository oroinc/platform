@ticket-BAP-23058
@fixture-OroUserBundle:user.yml
Feature: Disable login password form
  In order to have ability to manage user logins
  As administrator
  I need to have ability to disable user login form with password functionality

  Scenario: Default login
    Given I am on Login page
    Then I should see "Username"
    And I should see "Password"
    And I should see "Log in"
    When I fill "Login Form" with:
      | Username | admin |
      | Password | admin |
    And I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Check User page password functionality with enabled form
    Given I go to System/User Management/Users
    Then I should see following actions for charlie in grid:
      | Reset password |
    When I click view Charlie in grid
    And I follow "More actions"
    Then I should see "Change password"
    And I should see "Reset password"
    When I go to System/User Management/Users
    And I click "Create User"
    Then I should see "Password"
    And I should see "Re-enter password"

  Scenario: Disable login form
    Given I go to System / Configuration
    When I follow "System Configuration/General Setup/User Login" on configuration sidebar
    Then I should see "Password restrictions"
    And I should see "Login attempts"
    When I uncheck "Use default" for "Enable Username/Password Login" field
    And I uncheck "Enable Username/Password Login"
    Then I should not see "Password restrictions"
    And I should not see "Login attempts"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check User page password functionality with disabled form
    Given I go to System/User Management/Users
    Then I should not see following actions for charlie in grid:
      | Reset password |
    When click view Charlie in grid
    And follow "More actions"
    Then I should not see "Change password"
    And I should not see "Reset password"
    When I go to System/User Management/Users
    And click "Create User"
    Then I should not see "Password"
    And I should not see "Re-enter password"

  Scenario: Check Login page with disabled form
    When I click Logout in user menu
    Then I should be on Login page
    And I should not see "Login"
    And I should not see "Username"
    And I should not see "Password"
    And I should not see "Log in"
