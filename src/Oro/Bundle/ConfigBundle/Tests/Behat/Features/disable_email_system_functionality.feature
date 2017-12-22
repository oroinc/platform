@regression
@fixture-OroConfigBundle:disable_emails_stuff.yml
Feature: Disable Email system functionality
  In order to lightweight the system
  As Administrator
  I need have ability to turn off the Email functionality

  Scenario: Emails functionality enabled
    Given I login as administrator
    When I go to Dashboards/Dashboard
    Then should see "Recent Emails"
    And I should see an "Recent Emails" element
    When I go to System/Scheduled Tasks
    Then I should see following records in grid:
      | oro:cron:email-body-sync |
      | oro:cron:imap-sync       |
    And I should see My emails in user menu
    When I go to System/ User Management/ Users
    And click View Charlie in grid
    And follow "More actions"
    Then I should see "Send Email"
    And I should see "Merry Christmas" email in activity list

  Scenario: Administrator disables Emails
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I fill "System Config Form" with:
      | Enable User Emails | false |
    And I save form
    Then I should not see My emails in user menu

  Scenario: Administrator see widgets disabled
    When I go to Dashboards/Dashboard
    Then I should not see "Recent Emails"

  Scenario: Administrator see Email Synchronization inactive
    When I go to System/Scheduled Tasks
    Then I should not see "oro:cron:email-body-sync"
    And should not see "oro:cron:imap-sync"

  Scenario: No Email Action on user view page
    Given I go to System/ User Management/ Users
    And click View Charlie in grid
    And follow "More actions"
    Then I should not see "Send Email"
