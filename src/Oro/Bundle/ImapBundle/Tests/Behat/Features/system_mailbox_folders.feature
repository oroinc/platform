@ticket-BAP-21500
@fixture-OroImapBundle:system-mailbox.yml

Feature: System mailbox folders
    In order to manage Email feature
    As an Administrator
    I should be able to manipulate system mailbox folders

    Scenario: Check selected system mailbox folders stay selected after the form save
        Given I login as administrator
        And I go to System/ Configuration
        And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
        And click edit test system mailbox in grid
        Then the "Inbox" checkbox should be checked
        And the "Sent" checkbox should be checked
        And the "Another1" checkbox should be checked
        And the "Another2" checkbox should be checked
        And the "Another3" checkbox should be checked
        When I uncheck "Inbox"
        And I uncheck "Another1"
        And I click "Save"
        Then I should see "Could not establish the IMAP connection"
        And the "Inbox" checkbox should be unchecked
        And the "Sent" checkbox should be checked
        And the "Another1" checkbox should be unchecked
        And the "Another2" checkbox should be checked
        And the "Another3" checkbox should be checked
