@fixture-UserWorkflowFixture.yml
Feature: Workflow transition notifications
  In order to check notifications for workflow transitions
  As an Administrator
  I want to be able to set transition for notification rules

  Scenario: Workflow notification rule creation
    Given I login as administrator
    Then I go to System/Emails/Notification Rules
    And I press "Create Notification Rule"
    And I fill form with:
      | Entity Name     | User                  |
      | Email           | test@tst.ts           |
      | Event Name      | Workflow transition   |
    And I fill form with:
      | Template        | authentication_code                         |
      | Workflow        | oro.workflow.user_workflow_definition.label |
    And I fill form with:
      | Transition      | oro.workflow.user_workflow_definition.transition.start_transition.label (start_transition)  |
    When I save and close form
    Then I should see "Email notification rule saved" flash message
    And I should see User in grid with following data:
      | Event Name      | Workflow transition                                                                         |
      | Workflow        | oro.workflow.user_workflow_definition.label                                                 |
      | Transition      | oro.workflow.user_workflow_definition.transition.start_transition.label (start_transition)  |
