@fixture-OroWorkflowBundle:UserWorkflowFixture.yml
Feature: Workflow transition notifications
  In order to check notifications for workflow transitions
  As an Administrator
  I want to be able to set transition for notification rules

  Scenario: Feature background
    Given I login as administrator

  Scenario: Create an Email notification rule for workflow transition
    Given I go to System/Emails/Notification Rules
    And I click "Create Notification Rule"
    And I fill form with:
      | Entity Name | User |
    And I save and close form
    Then I should see "This value should not be blank."

    And I fill form with:
      | Event Name | Workflow transition |
    And I save and close form
    Then I should see "This value should not be blank."

    When I fill form with:
      | Workflow | First Workflow |
    And I save and close form
    Then I should see "This value should not be blank."

    When I fill form with:
      | Transition | First Workflow Transition (start_transition) |
    And I save and close form
    Then I should see "This value should not be blank."

    When I fill form with:
      | Template | authentication_code |
      | Email    | test@example.com    |
    And I save and close form
    Then I should see "Email notification rule saved" flash message
    And I should see First Workflow in grid with following data:
      | Entity Name     | User                                         |
      | Event Name      | Workflow transition                          |
      | Workflow        | First Workflow                               |
      | Transition name | First Workflow Transition (start_transition) |
      | Template        | authentication_code                          |
      | Recipient email | test@example.com                             |

  Scenario: Edit an Email notification rule for workflow transition
    Given I click on First Workflow in grid
    When I fill form with:
      | Workflow   | Second Workflow |
      | Transition | Second Workflow Transition (start_transition) |
      | Template   | user_reset_password                           |
    And I save and close form
    Then I should see "Email notification rule saved" flash message
    And I should see Second Workflow in grid with following data:
      | Entity Name     | User                                          |
      | Event Name      | Workflow transition                           |
      | Workflow        | Second Workflow                               |
      | Transition name | Second Workflow Transition (start_transition) |
      | Template        | user_reset_password                           |
      | Recipient email | test@example.com                              |

  Scenario: Deleting workflow
    Given I click Delete Second Workflow in grid
    When I confirm deletion
    Then I should see "Item deleted" flash message
    And there is no "Second Workflow" in grid
