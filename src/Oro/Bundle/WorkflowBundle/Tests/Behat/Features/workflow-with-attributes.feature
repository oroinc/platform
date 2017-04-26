@fixture-../../../../../UserBundle/Tests/Behat/Features/Fixtures/user.yml
@fixture-../../../../../OrganizationBundle/Tests/Behat/Features/Fixtures/BusinessUnit.yml
Feature: Adding attributes for workflow transition
  In order to specify attributes for workflow transitions
  As an Administrator
  I want to be able to specify the workflow transition attributes in workflow management UI

  Scenario: Create a workflow
    Given I login as administrator
    When I go to System/Workflows
    And I press "Create Workflow"
    And I fill "Workflow Edit Form" with:
      | Name           | User Workflow Test |
      | Related Entity | User               |
# Add step
    And I press "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step One |
    And I press "Apply"
# Add transition
    And I press "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name      | Transition One |
      | From step | (Start)        |
      | To step   | Step One       |
    And I click "Attributes"
# Add attribute for scalar property
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name        |
      | Label        | User`s first name |
    And I press "Add"
# Add attribute for object property
    And I fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | Owner        |
      | Label        | User`s owner |
    And I press "Add"
    And I press "Apply"
    And I save and close form
    And I press "Activate"
# press Activate button in popup
    And I press "Activate"
# Update cache
    When I go to System/Localization/Translations
    And I press "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Verify Transition
    Given I go to System/User Management/Users
    And I click view Charlie in grid
    When I click "Transition One"
    And I fill in "User`s first name" with "New Charlie`s Name"
    And I fill in "User`s owner" with "Child Business Unit"
    When click "Submit"
    Then I should see "Step One"
    And I should see "New Charlie`s Name"
    And I should see "Child Business Unit"
