@ticket-BAP-13968
@automatically-ticket-tagged
@fixture-OroWorkflowBundle:Workflow.yml
Feature: Transition Destination Page for Workflow
  In order to specify where the user should be redirected after submitting workflow transition form (shown as a separate page)
  As an Administrator
  I want to be able to specify the transition destination page in workflow management UI

  Scenario: Create a workflow with Transition Destination Page via UI
    Given I login as administrator
    When I go to System/Workflows
    And I click "Create Workflow"
    And I fill "Workflow Edit Form" with:
      | Name                  | User Workflow Test |
      | Related Entity        | User               |
# Add design for Entity View Page
    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name     | Step Entity View Page |
      | Position | 10                    |
    And I click "Apply"
    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Transition Entity View Page |
      | From step        | (Start)                     |
      | To step          | Step Entity View Page       |
      | View form        | Separate page               |
      | Destination Page | Entity View Page            |
      | Warning message  | Test Entity View Page       |
    And I click "Attributes"
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name    |
      | Label        | Change a name |
    And I click "Add"
    And I click "Apply"
# Add design for Entity Index Page
    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name     | Step Entity Index Page |
      | Position | 20                     |
    And I click "Apply"
    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Transition Entity Index Page |
      | From step        | Step Entity View Page        |
      | To step          | Step Entity Index Page       |
      | View form        | Separate page                |
      | Destination Page | Entity Index Page            |
      | Warning message  | Test Entity Index Page       |
    And I click "Attributes"
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name    |
      | Label        | Change a name |
    And I click "Add"
    And I click "Apply"
# Add design for Original Page
    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name     | Step Original Page |
      | Position | 30                 |
    And I click "Apply"
    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Transition Original Page |
      | From step        | Step Entity Index Page   |
      | To step          | Step Original Page       |
      | View form        | Separate page            |
      | Destination Page | Original Page            |
      | Warning message  | Test Original Page       |
    And I click "Attributes"
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name    |
      | Label        | Change a name |
    And I click "Add"
    And I click "Apply"
    And I save and close form
    And I click "Activate"
# press Activate button in popup
    And I click "Activate"
    Then I should see "Deactivate"
# Update cache
    When I go to System/Localization/Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message
    And I click Logout in user menu

  Scenario: Verify Transition of Entity View Page
    Given I login as administrator
    When I go to System/User Management/Users
    And I click view Peter in grid
    And I click "Transition Entity View Page"
    Then I should see "Test Entity View Page"
    And I fill in "Change A Name" with "Peter 01"
    When click "Submit"
    Then I should see "Step Entity View Page"
    And I click Logout in user menu

  Scenario: Verify Transition of Entity Index Page
    Given I login as administrator
    When I go to System/User Management/Users
    And I click view Peter 01 in grid
    And I click "Transition Entity Index Page"
    Then I should see "Test Entity Index Page"
    And I fill in "Change A Name" with "Peter 02"
    When click "Submit"
    Then I should see Peter 02 in grid with following data:
      | Step | Step Entity Index Page |
    And I click Logout in user menu

  Scenario: Verify Transition of Original Page
    Given I login as administrator
    And I go to System/User Management/Users
    And I click view Peter in grid
    When I click "Transition Original Page"
    Then I should see "Test Original Page"
    And I fill in "Change A Name" with "Peter 03"
    When click "Submit"
    Then I should see "Step Original Page"
    And I should see "Peter 03"
    And I click Logout in user menu
