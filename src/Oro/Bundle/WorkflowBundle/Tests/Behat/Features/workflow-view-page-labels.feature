@ticket-BAP-15030
@fixture-OroWorkflowBundle:WorkflowLabelsFixture.yml
Feature: Status labels on workflow view page
  In order to view workflow status on view page
  As an Administrator
  I want to see System and Readonly labels on view page of workflow

  Scenario: Clean translation cache
    Given I login as administrator
    When I go to System/ Localization/ Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Check labels for custom editable workflow
    Given I go to System/ Workflows
    When I click view Custom editable workflow in grid
    Then I should see "Custom editable workflow"
    And I should not see an "Workflow System Label" element
    And I should not see an "Workflow Readonly Label" element

  Scenario: Check labels for custom readonly workflow
    Given I go to System/ Workflows
    And I click view Custom readonly workflow in grid
    Then I should see "Custom readonly workflow"
    And I should not see an "Workflow System Label" element
    And I should see an "Workflow Readonly Label" element

  Scenario: Check labels for system editable workflow
    Given I go to System/ Workflows
    And I click view System editable workflow in grid
    Then I should see "System editable workflow"
    And I should see an "Workflow System Label" element
    And I should not see an "Workflow Readonly Label" element

  Scenario: Check labels for system readonly workflow
    Given I go to System/ Workflows
    And I click view System readonly workflow in grid
    Then I should see "System readonly workflow"
    And I should see an "Workflow System Label" element
    And I should see an "Workflow Readonly Label" element
