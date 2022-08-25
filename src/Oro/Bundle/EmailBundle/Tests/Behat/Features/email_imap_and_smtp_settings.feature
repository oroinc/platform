@ticket-BAP-21510

Feature: Email IMAP and SMTP settings
  In order to manager Email setting of application
  As an Administrator
  I need to be able to check IMAP and SMTP settings

  Scenario: Check IMAP and SMTP settings in system configuration
    Given I login as administrator
    When I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I fill "Email Synchronization Settings System Config Form Other" with:
      | Enable IMAP     | true      |
      | IMAP Host       | host      |
      | IMAP Port       | 1         |
      | IMAP Encryption | SSL       |
      | Enable SMTP     | true      |
      | SMTP Host       | host      |
      | SMTP Port       | 1         |
      | Encryption      | SSL       |
      | User            | test_user |
      | Password        | unknown   |
    And I click "Check connection/Retrieve folders"
    Then I should see "Could not establish the SMTP connection"
    And I should see "Could not establish the IMAP connection"
    And I save form
    And I should see "At least one folder of mailbox is required to be selected."
