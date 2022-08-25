@ticket-BAP-21510
@fixture-OroEmailBundle:other_users_emails_displayed_in_grid_in_my_emails_page.yml

Feature: Create mailbox, check autocomplete users in Access Management
  As an Administrator

  Scenario: Create Mailbox
    And I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I click "Add Mailbox"
    When I fill form with:
      | Mailbox Label | Test Mailbox     |
      | Email         | test@example.com |
    And I save form
    Then I should see "Test Mailbox has been saved" flash message

  Scenario: Check autocomplete users in Access Management
    When I click "Edit" on row "test@example.com" in grid
    Then I should see the following options for "Users" select:
      | John Doe - admin@example.com (admin)               |
      | Samantha Parker - Samantha1@example.com (samantha) |
      | Charlie Sheen - Charlie1@example.com (charlie)     |

  Scenario: Disable user Charlie Sheen
    When I go to System/User Management/Users
    Then I click Disable charlie in grid

  Scenario: Check autocomplete users in Access Management with disabled user
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I click "Edit" on row "test@example.com" in grid
    Then I should see the following options for "Users" select:
      | John Doe - admin@example.com (admin)               |
      | Samantha Parker - Samantha1@example.com (samantha) |

  Scenario: MailboxTypeTest
    And I fill form with:
      | Users | [John Doe, Samantha Parker]       |
      | Roles | [Administrator, Account Manager]] |
    And I save form
    Then I should see "Test Mailbox has been saved" flash message
    And should see "Samantha Parker"
    And should see "John Doe"
    And should see "Account Manager"
    And should see "Administrator"
