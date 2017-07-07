@fixture-OroWorkflowBundle:Users.yml
@fixture-OroWorkflowBundle:TestWorkflow.yml
Feature: Workflow entity attribute
  Scenario: Set up workflow variable configuration
    Given I login as administrator
    When go to System/ Localization/ Translations
    And press "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

    When I go to System/ User Management/ Users
    And I click "View" on row "User1 First Name User1 Last Name" in grid
    And I press "first transition"
    Then I should see "UiDialog" with elements:
      | Content | start message -  - end message |
    And click "Cancel"

  Scenario: Check that value of entity variable correct displayed
    Given I go to System/ Workflows
    When I click "Configuration" on row "Test Workflow" in grid
    And I fill form with:
      | count | 100000                                                       |
      | user  | User2 First Name User2 Last Name - user2@example.com (user2) |
      | guest | User1 First Name User1 Last Name - user1@example.com (user1) |
    And I save and close form
    Then I should see "Workflow configuration successfully updated" flash message

    When I go to System/ User Management/ Users
    And I click "View" on row "User1 First Name User1 Last Name" in grid
    And I press "first transition"
    Then I should see "UiDialog" with elements:
      | Content | start message - User2 First Name User2 Last Name - end message |
    And click "Cancel"
