@ticket-BAP-14137
@automatically-ticket-tagged
@fixture-OroUserBundle:user.yml
@fixture-OroOrganizationBundle:BusinessUnit.yml
Feature: Adding attributes for workflow transition
  In order to specify attributes for workflow transitions
  As an Administrator
  I want to be able to specify the workflow transition attributes in workflow management UI

  Scenario: Create a workflow
    Given I login as administrator
    When I go to System/Workflows
    And I click "Create Workflow"
    And I fill "Workflow Edit Form" with:
      | Name           | User Workflow Test |
      | Related Entity | User               |
# Add step
    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step One |
    And I click "Apply"
# Add transition
    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name      | Transition One |
      | From step | (Start)        |
      | To step   | Step One       |
    And I click "Attributes"
# Add attribute for scalar property
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name        |
      | Label        | User`s first name |
    And I click "Add"
# Add attribute for object property
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | Owner        |
      | Label        | User`s owner |
    And I click "Add"
    And I click "Apply"
    And I save and close form
# Update cache
    When I go to System/Localization/Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Edit a workflow
    Given I login as administrator
    When I go to System/Workflows
    And I click edit User Workflow Test in grid
    And I click "Transition One"
    And I click "Attributes"
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | Avatar        |
      | Label        | User`s avatar |
    And I click "Add"
    And I click "Apply"
    And I save and close form

  Scenario: Clone a workflow
    Given I login as administrator
    When I go to System/Workflows
    And I click clone User Workflow Test in grid
    And I fill "Workflow Edit Form" with:
      | Name           | Cloned User Workflow Test |
      | Related Entity | User                      |
    And I click "Transition One"
    And I click "Attributes"
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | Organization        |
      | Label        | User`s organization |
    And I click "Add"
    And I click "Apply"
    And I save and close form
    And I click "Activate"
# press Activate button in popup
    And I click "Activate"
# Update cache
    When I go to System/Localization/Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Verify Transition
    Given I go to System/User Management/Users
    And I click view Charlie in grid
    When I click "Transition One"
    And I fill in "User`s first name" with "New Charlie`s Name"
    And I fill in "User`s owner" with "Child Business Unit"
    And I should see "User`s avatar"
    And I should see "User`s organization"
    When click "Submit"
    Then I should see "Step One"
    And I should see "New Charlie`s Name"
    And I should see "Child Business Unit"
