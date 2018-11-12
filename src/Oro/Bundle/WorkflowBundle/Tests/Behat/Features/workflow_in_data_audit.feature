@ticket-BAP-16008
Feature: Workflow in Data Audit
  In order to check workflow step changes
  As an Administrator
  I want to be able to see step changes in audit grid

  Scenario: Workflow creation for Organization entity
    Given I login as administrator
    When I go to System / Workflows
    And I click "Create Workflow"
    And I fill form with:
      | Name            | Test Organization Workflow |
      | Related Entity  | Organization               |
    And I click "Add step"
    And I fill form with:
      | label           | Step1 |
    And I click "Apply"
    And I click "Add transition"
    And I fill form with:
      | label           | First Transit |
      | step_from       | (Start)       |
      | step_to         | Step1         |
    And I click "Apply"
    And I click "Add step"
    And I fill form with:
      | label           | Step2 |
      | order           | 1     |
      | is_final        | true  |
    And I click "Apply"
    And I click "Add transition"
    And I fill form with:
      | label           | Second Transit |
      | step_from       | Step1          |
      | step_to         | Step2          |
    And I click "Apply"
    And I save and close form
    Then I should see "Workflow saved" flash message
    And I should see "Translation cache update is required. Click here to update" flash message

  Scenario: Prepare workflow to usage with audit
    When I click "Activate"
    And I click "Activate"
    Then I should see "Workflow activated" flash message
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Changes in workflow steps for Organization entity should be showed in data audit
    When I go to System / User Management / Organizations
    And I click View Oro in grid
    And I click "First Transit"
    And I click "Change History"
    Then I should see following "Audit History Grid" grid:
      | Old Values                  | New Values                        |
      | Test Organization Workflow: | Test Organization Workflow: Step1 |
    When I close ui dialog
    And I click "Second Transit"
    And I click "Change History"
    Then I should see following "Audit History Grid" grid:
      | Old Values                        | New Values                        |
      | Test Organization Workflow: Step1 | Test Organization Workflow: Step2 |
