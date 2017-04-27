@ticket-OEE-1217
@automatically-ticket-tagged
@not-automated
Feature: Disable Emails per organization
  In order to lightweight the system not for all users
  As Administrator
  I need to turn off the Email functionality for current organization

  Scenario: Feature background
    Given following user exists:
      | First Name | Last Name | Username | Password  | Role          |
      | John       | Connor    | joconn   | Qwe123qwe | Sales Rep     |
      | Sara       | Connor    | saconn   | Qwe123qwe | Administrator |
    And there is "Organization" presented with following data:
      | Name      | HAS ORGANIZATION |
      | Albatross | joconn, saconn   |
      | BadHat    | joconn, saconn   |
      | Concord   | joconn, saconn   |
    And "Recent Emails" widget is added to Dashboard on all Organizations
    And "Recent Emails" sidebar widget is added to Dashboard on all Organizations
    And "Email Synchronization" is set up and running on all Organizations
    And "System Mailbox" is configured on all Organizations

  Scenario: Administrator disables Emails for Albatross organization
    Given I login as "joconn" user
    And I go to System/User Management/Organizations
    And I open Albatross entity
    And I click "Configuration"
    And I go to Email Configuration
    When I uncheck "Enable User Emails"
    And I save setting
    Then I login as "joconn" user
    And I see "User Menu"
    And I can't see "My Emails" link in it
    And I can't see "Recent Emails" menu
    And I can't see "Emails" section in "System" menu
    But I switch to BadHat organization
    Then I see "User Menu"
    And I can see "My Emails" link in it
    And I can see "Recent Emails" menu
    And I can see "Emails" section in "System" menu

  Scenario: User see widgets disabled
    Given I switch to Albatross organization
    And I open Dashboard
    Then I can't see "Recent Emails" widget
    But I switch to BadHat organization
    Then And I open Dashboard
    And I can see "Recent Emails" widget

  Scenario: Administrator user see Email Synchronization Settings disabled
    Given I login as "saconn" user
    And I switch to Albatross organization
    And I go to System/Configuration
    Then I can't see "Email Configuration" section
    And I can't see Integrations related to Emails
    But I switch to BadHat organization
    And I go to System/Configuration
    Then I can see "Email Configuration" section
    And I can see Integrations related to Emails

  Scenario: Administrator user see Email Synchronization inactive
    Given I switch to Albatross organization
    And I go to System/Job Queue
    Then I can't see active email sync jobs
    But I switch to BadHat organization
    And I go to System/Job Queue
    Then I can see active email sync jobs

  Scenario: Administrator user see Email-unrelated items
    Given I switch to Albatross organization
    And I am on the homepage
    And see Templates menu in System/Emails menu
    And see Notification Rules menu in System/Emails menu
    And see Maintenance Notifications menu in System/Emails menu
    And see Email Campaign menu in Marketing menu
    But I switch to BadHat organization
    And I am on the homepage
    And see Templates menu in System/Emails menu
    And see Notification Rules menu in System/Emails menu
    And see Maintenance Notifications menu in System/Emails menu
    And see Email Campaign menu in Marketing menu

  Scenario: Administrator disables Emails for the system
    Given I go to I go to System/Configuration/Email Configuration
    When I uncheck "Enable User Emails"
    And I save setting
    And I login as "joconn" user
    And I switch to Concord organization
    Then I see "User Menu"
    And I can't see "My Emails" link in it
    And I can't see "Recent Emails" menu
    And I can't see "Emails" section in "System" menu

  Scenario: New organization is created
    Given I login as "saconn" user
    And create new "Organization" with following data:
      | Name    | HAS ORGANIZATION |
      | Darjing | joconn, saconn   |
    And I go to System/User Management/Organizations
    And I open Darjing entity
    And I click "Configuration"
    And I go to Email Configuration
    When I uncheck "Use System"
    And I save setting
    And I login as "joconn" user
    Then I see "User Menu"
    And I can see "My Emails" link in it
    And I can see "Recent Emails" menu
    And I can see "Emails" section in "System" menu