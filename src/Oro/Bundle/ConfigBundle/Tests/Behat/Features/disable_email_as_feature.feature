Feature: Disable Emails
  In order to lightweight the system
  As Administrator
  I need to turn off the Email functionality

  Scenario: Feature background
    Given "Recent Emails" widget is added to Dashboard
    And "Recent Emails" sidebar widget is added to Dashboard
    And "Email Synchronization" is set up and running
    And "System Mailbox" is configured

  Scenario: Administrator disables Emails
    Given I login as "Administrator" user
    And I go to System/Configuration/Email Configuration
    When I uncheck "Enable User Emails"
    And I save setting
    Then I see "User Menu"
    But I can't see "My Emails" link in it
    And I can't see "Recent Emails" menu
    And I can't see "Emails" section in "System" menu

  Scenario: Administrator see widgets disabled
    Given I open Dashboard
    Then I can't see "Recent Emails" widget

  Scenario: Administrator see Email Synchronization Settings disabled
    Given I go to System/Configuration
    Then I can't see "Email Configuration" section
    And I can't see Integrations related to Emails

  Scenario: Administrator see Email Synchronization inactive
    Given I go to System/Job Queue
    Then I can't see active email sync jobs

   Scenario:  Administrator see Email-unrelated items
     Given I am on the homepage
     And see Templates menu in System/Emails menu
     And see Notification Rules menu in System/Emails menu
     And see Maintenance Notifications menu in System/Emails menu
     And see Email Campaign menu in Marketing menu

  Scenario: Administrator enables Emails
    Given I login as "Administrator" user
    And I go to System/Configuration/Email Configuration
    When I check "Enable User Emails"
    And I save setting
    Then I see "User Menu"
    And I see "My Emails" link in it
    And I see "Recent Emails" menu
    And I see "Emails" section in "System" menu

  Scenario: Administrator see widgets enabled
    Given I open Dashboard
    Then I see "Recent Emails" widget

  Scenario: Administrator see Email Synchronization Settings enabled
    Given I go to System/Configuration
    Then I see "Email Configuration" section
    And I see Integrations related to Emails

  Scenario: Administrator see Email Synchronization active
    Given I go to System/Job Queue
    Then I see active email sync jobs