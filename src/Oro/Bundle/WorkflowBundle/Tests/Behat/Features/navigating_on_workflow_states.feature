@ticket-BAP-16165
Feature: Navigating on workflow states
  In order to create or edit workflow
  As an Administrator
  I want to be able to navigate workflow states

  Scenario: Workflow creation for Organization entity
    Given I login as administrator
    And I go to System / Workflows
    And I click "Create Workflow"
    And I fill form with:
      | Name            | Test Workflow |
      | Related Entity  | Organization  |
    And I click "Add step"
    And I fill form with:
      | label           | First step |
    And I click "Apply"
    And I click "Add transition"
    And I fill form with:
      | label           | First Transit |
      | step_from       | (Start)       |
      | step_to         | First step    |
    And I click "Apply"
    And I save and close form
    When I click "Edit"
    And I click "Add step"
    And I fill form with:
      | label           | Second step |
    And I click "Apply"
    And I click "Undo"
    Then I should not see "Second step"
