Feature: Disable Emails as a feature
  In order to lightweight the system
  As Administrator
  I need to turn off the Email functionality

  Background:
    Given "Recent Emails" widget is added to Dashboard
    And "Recent Emails" sidebar widget is added to Dashboard
    And "Email Synchronization" is set up and running
    And "System Mailbox" is configured

  Scenario: Disable Emails feature
    Given I login as "Administrator" user
    And I go to System/Configuration/Email Configuration
    #should be clarified
    When I uncheck "Enable User Emails"
    And I save setting
    Then I see "User Menu"
    But I can't see "My Emails" link in it
    And I can't see "Recent Emails" menu
    And I can't see "Emails" section in "System" menu
    And I can't see "Email Campaigns" menu in "Marketing" menu

  Scenario: Check disabled widgets
    Given I open Dashboard
    Then I can't see "Recent Emails" widget


  Scenario: Check Email Synchronization Settings
    Given I go to System/Configuration
    Then I can't see "Email Configuration" section
    And I can't see Intergarions related to Emails

  Scenario: Check Email Synchronization
    Given I go to System/Job Queue
    Then I can't see active email sync jobs