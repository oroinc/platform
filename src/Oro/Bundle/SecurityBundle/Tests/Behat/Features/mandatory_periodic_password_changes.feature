@ticket-OEE-1187
@automatically-ticket-tagged
@not-automated
Feature: Mandatory periodic password changes
  In order to increase login security level
  As Administrator
  I need to manage time periods for password change

  Scenario: Feature background
    Given following user exists:
    | First Name | Last Name | Username | Password  | Role      |
    | John       | Connor    | joconn   | Qwe123qwe | Sales Rep |

  Scenario: Administrator configures Emails
    Given I login as "Administrator" user
    And I go to System/Configuration/User Configuration
    When I fill in "Period of password change" with "7 days"
    And I fill in "Last passwords amount storing" with "2"
    And I save setting
    And login as "joconn" user
    Then I should see "Please, be notified that you should change your password within 7 days" flash message

  Scenario: Second notification coming
    Given I wait for 4 days
    Then I should see "Please, be notified that you should change your password within 3 days" flash message

  Scenario: Third notification coming
    Given I wait for 2 days
    Then I should see "Please, be notified that you should change your password within 1 day" flash message

  Scenario: Password change with the same one
    Given I go to User Menu/My user
    And I change the password
    When I fill in "New password" with "Qwe123qwe"
    Then I should see "New password should be different from previos 2 passwords" flash message

  Scenario: Password change with tha new one
    Given I go to User Menu/My user
    And I change the password
    When I fill in "New password" with "Rty456rty"
    Then password should be changed successfully.

  Scenario: Password change with previous two
    Given I go to User Menu/My user
    And I change the password
    When I fill in "New password" with "Rty456rty"
    Then I should see "New password should be different from previos 2 passwords" flash message
    But I fill in "New password" with "Qwe123qwe"
    Then I should see "New password should be different from previos 2 passwords" flash message
    But I fill in "New password" with "Uio789uio"
    Then password should be changed successfully.

  Scenario: Password changes by Administrator
    Given I login as "Administrator" user
    And I go to System/Users/User management
    And I open "joconn" user
    And I change the password
    When I fill in "New password" with "Rty456rty"
    Then I should see "New password should be different from previos 2 passwords" flash message
    But I fill in "New password" with "Uio789uio"
    Then I should see "New password should be different from previos 2 passwords" flash message
    But I fill in "New password" with "Qwe123qwe"
    Then password should be changed successfully.

  Scenario: Password changes by Administrator
    Given I login as "Administrator" user
    And I go to System/Users/User management
    And I open "joconn" user
    And I reset the password
    And I go to "joconn" user mailbox
    And I proceed with password reset
    When I fill in "New password" with "Rty456rty"
    Then I should see "New password should be different from previos 2 passwords" flash message
    But I fill in "New password" with "Qwe123qwe"
    Then I should see "New password should be different from previos 2 passwords" flash message
    But I fill in "New password" with "Uio789uio"
    Then password should be changed successfully.
