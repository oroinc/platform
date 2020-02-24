@fixture-OroUserBundle:user.yml
Feature: Notification for disabled_users
  In order to use notification rules
  As an Administrator
  I want to not send notification for disabled users

  Scenario: Create email notification rule for Task entity
    Given I login as administrator
    When I go to System / Emails / Notification Rules
    And click "Create Notification Rule"
    And fill form with:
      | Entity Name | Task           |
      | Event Name  | Entity create  |
      | Template    | task_reminder  |
      | Groups      | Administrators |
    When I save and close form
    Then I should see "Notification Rule saved" flash message

  Scenario: Ensure that email could be sent for enabled user
    When I go to Activities / Tasks
    And I click "Create Task"
    And fill form with:
      | Subject | Test task |
    And save and close form
    Then should see "Task saved" flash message
    And email with Subject "Test task is due on" containing the following was sent:
      | To | charlie@example.com |

  Scenario: Disable user and create Task
    When I go to System / User Management / Users
    And I click Disable charlie in grid
    Then I should see "User charlie disabled." flash message

    When I go to Activities / Tasks
    And I click "Create Task"
    And fill form with:
      | Subject | New test task |
    And save and close form
    Then should see "Task saved" flash message
    And email with Subject "New test task is due on" was not sent
