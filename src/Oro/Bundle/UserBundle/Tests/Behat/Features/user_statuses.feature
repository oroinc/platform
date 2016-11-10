Feature: User statuses
  In order to manage activity of users
  As Administrator
  I need to view and see changes of User statuses

  Scenario: Make User available for login
    Given the following user:
      | Username   | Password  | Status  | Role      |
      | mattjohnes | Qwe123qwe | Enabled | Sales Rep |
    When I open User for editing
    And set Status = "Inactive"
    And I save and close form
    Then user with "mattjohnes" username has no possibility to login to the system.

  Scenario: Make User unavailable for login
    Given I open "mattjohnes" User for editing
    And set Status = "Active"
    And I save and close form
    Then user with "mattjohnes" username could login to the system.