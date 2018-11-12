@fixture-OroWorkflowBundle:Users.yml
@fixture-OroWorkflowBundle:TestWorkflow.yml
Feature: Workflow variables
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Set up workflow variable configuration
    Given I login as administrator
    When go to System/ Localization/ Translations
    And click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

    When I go to System/ User Management/ Users
    And I click "View" on row "User1 First Name User1 Last Name" in grid
    And I click "first transition"
    Then I should see "UiDialog" with elements:
      | Content | start message -  - end message |
    And click "Cancel"

  Scenario: Check display of variables on view page
    When I go to System/ Workflows
    And I click "View" on row "Test Workflow" in grid
    Then I should see "count 99"
    And I should see "Test Bool Var Yes"
    And I should see "Test Array Var value1, value2"
    And I should see "guest N/A"
    And I should see "user N/A"

    When I go to System/ Workflows
    And I click "Configuration" on row "Test Workflow" in grid
    And I fill form with:
      | count         | 100000                                             |
      | user          | User2 First Name User2 Last Name user2@example.com |
      | guest         | User1 First Name User1 Last Name user1@example.com |
      | Test Bool Var | false                                              |
    And I save and close form
    Then I should see "Workflow configuration successfully updated" flash message

    When I go to System/ Workflows
    And I click "View" on row "Test Workflow" in grid
    Then I should see "count 100000"
    And I should see "Test Bool Var No"
    And I should see "guest user1"
    And I should see "user user2"

  Scenario: Check that value of entity variable correct displayed
    When I go to System/ User Management/ Users
    And I click "View" on row "User1 First Name User1 Last Name" in grid
    And I click "first transition"
    Then I should see "UiDialog" with elements:
      | Content | start message - User2 First Name User2 Last Name - end message |
    And click "Cancel"
