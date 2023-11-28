@regression
@ticket-BB-23057
@fixture-OroUserBundle:second-admin.yml

Feature: Check whether the pinned pages are unique for each user and do not depend on each other

  Scenario: Feature Background
    Given sessions active:
      | Admin  | first_session  |
      | Admin1 | second_session |

  Scenario: Change Task entity permissions
    Given I proceed as the Admin
    And login as administrator
    When I go to System / User Management / Roles
    And click Edit "Administrator" in grid
    And select following permissions:
      | Task | View:User |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Create pinned page from Admin user
    Given I go to Activities/ Tasks
    And click "Create Task"
    And fill "Task Form" with:
      | Subject | Ticket from Admin user |
    And pin page
    Then Create Task link must be in pin holder

  Scenario: Make sure that the Admin1 user should not see the pinned pages created by the Admin user
    Given I proceed as the Admin1
    And I login as "charlie" user

    When I go to Activities/ Tasks
    Then Create Task link must not be in pin holder
    And number of records should be 0

    When I click "Create Task"
    And fill "Task Form" with:
      | Subject | Ticket from Admin1 user |
    And pin page
    Then Create Task link must be in pin holder

  Scenario: Save ticket from Admin user
    Given I proceed as the Admin
    And go to Dashboards/Dashboard
    And follow Create Task link in pin holder
    When I unpin page
    Then Create Task link must not be in pin holder

    When save and close form
    Then should see "Task saved" flash message
    And go to Activities/ Tasks
    And should see following grid:
      | Subject                |
      | Ticket from Admin user |
    And number of records should be 1

  Scenario: Save ticket from Admin1 user
    Given I proceed as the Admin1
    And go to Dashboards/Dashboard
    And follow Create Task link in pin holder
    When I unpin page
    Then Create Task link must not be in pin holder

    When I save and close form
    Then should see "Task saved" flash message
    And go to Activities/ Tasks
    And should see following grid:
      | Subject                 |
      | Ticket from Admin1 user |
    And number of records should be 1
