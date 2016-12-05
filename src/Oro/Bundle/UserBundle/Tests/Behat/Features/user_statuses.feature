@skip
# todo: unskip this feature while resolve BAP-12862
Feature: User statuses
  In order to allow or deny users to login
  As Administrator
  I need to change User statuses

  Scenario: Make User available for login
    Given Charlie Sheen active user exists in the system
    And I login as administrator
    When I open "Charlie Sheen" user edit page
    And I select "Inactive" from "Status"
    And I save and close form
    Then Charlie Sheen user has no possibility to login to the Dashboard

  Scenario: Make User unavailable for login
    Given I open "Charlie Sheen" user edit page
    And select "Active" from "Status"
    When I save and close form
    Then Charlie Sheen user could login to the Dashboard
