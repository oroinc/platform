Feature: User statuses
  In order to allow or deny users to login
  As Administrator
  I need to change User statuses

  Scenario: Make User available for login
    Given the following user:
      | Username   | Fisrt Name | Last Name  | Password  | Status  | Role     |
      | mattjohnes | Matt       | Johnes     | Qwe123qwe | Active | Sales Rep |
    When I open Matt Johnes user edit page
    And I select "Inactive" from "Status"
    And I save and close form
    Then Matt Johnes user has no possibility to login to the Dashboard.

  Scenario: Make User unavailable for login
    Given I open Matt Johnes user edit page
    And I select "Active" from "Status"
    And I save and close form
    Then Matt Johnes user could login to the Dashboard.