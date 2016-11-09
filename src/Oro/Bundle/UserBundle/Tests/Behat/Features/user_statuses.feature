Feature: User statuses
  In order to manage activity of users
  As Administrator
  I need to view and see changes of User statuses

  Scenario: Feature background
    Given the following users:
      | Username   | Password  | Status         | Authentication Status | Role                  |
      | mattjohnes | Qwe123qwe | Enabled        | Active                | Sales Rep             |
      | joemann    | Qwe123qwe | Disabled       | Active                | Leads Development Rep |
      | harrylee   | Qwe123qwe | Enabled        | Locked                | Marketing Manager     |
      | roothmio   | Qwe123qwe | Enabled        | Locked                | Online Sales Rep      |
      | tonyjack   | Qwe123qwe | Enabled        | Password Reset        | Sales Manager         |
      | liltoxy    | Qwe123qwe | Enabled        | Password Reset        | Administrator         |

  Scenario: Observe Users with different statuses
    Given I login as "Administrator" user
    When I go to System/User Management/Users
    Then I should see following users
      | Username   | Status         | Authentication Status |
      | mattjohnes | Enabled        | Active                |
      | joemann    | Disabled       | Active                |
      | harrylee   | Enabled        | Locked                |
      | roothmio   | Enabled        | Locked                |
      | tonyjack   | Enabled        | Password Reset        |
      | liltoxy    | Enabled        | Password Reset        |

  Scenario: Create User with "Enabled" status
    Given I go to System/User Management/Users
    And start to create User
    And I fill "Create User" form with:
      | Status            | Active                |
      | Username          | chrismoore            |
      | Password          | Qwe123qwe             |
      | Re-Enter Password | Qwe123qwe             |
      | First Name        | Chris                 |
      | Last Name         | Moore                 |
      | Primary Email     | chrismoore@google.com |
      | Role              | Administrator         |
    When I save and close form
    Then I should see "All Users" grid
    And user with "chrismoore" username could login to the system.

  Scenario: Create User with "Disabled" status
    Given I go to System/User Management/Users
    And start to create User
    And I fill "Create User" form with:
      | Status            | Inactive            |
      | Username          | jasontann           |
      | Password          | Qwe123qwe           |
      | Re-Enter Password | Qwe123qwe           |
      | First Name        | Jason               |
      | Last Name         | Tann                |
      | Primary Email     | jasontann@gmail.com |
      | Role              | Sales Rep           |
    When I save and close form
    Then user with "jasontann" username has no possibility to login to the system.

  Scenario: Disable User
    Given the following user:
      | Username   | Password  | Status  | Authentication Status | Role      |
      | mattjohnes | Qwe123qwe | Enabled | Active                | Sales Rep |
    When I open User for editing
    And set Status = "Inactive"
    And I save and close form
    Then user with "mattjohnes" username has no possibility to login to the system.

  Scenario: Enable User
    Given I open "mattjohnes" User for editing
    And set Status = "Active"
    And I save and close form
    Then user with "mattjohnes" username could login to the system.