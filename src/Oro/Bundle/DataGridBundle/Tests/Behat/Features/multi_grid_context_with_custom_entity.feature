@ticket-BAP-16169

Feature: Multi grid context with custom entity
  As Administrator user
  I need to have ability to create Custom entity and use its records in Context for e.g. Tasks, Emails, etc.

  Scenario: Create Custom entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name         | testEntity    |
      | Label        | Test Entity   |
      | Plural Label | Test Entities |
    And I check "Tasks"
    And I check "Emails"
    And I save and close form
    Then I should see "Entity saved" flash message
    When I click "Create Field"
    And I fill form with:
      | Field name | Name   |
      | Type       | String |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"
    When I click update schema
    And I should see Schema updated flash message

  Scenario: Create a record for Custom entity
    When I go to System/Entities/Test Entity
    And I click "Create Test Entity"
    When I fill form with:
      | Name | Test Record |
    And I save and close form
    Then I should see "Entity saved" flash message

  Scenario: Check if Custom entity is available in Task context and context switcher is operational
    When I go to Activities/Tasks
    And I click "Create Task"
    When I fill form with:
      | Subject | Test task |
    And I save and close form
    Then I should see "Task saved" flash message
    And I click "Add Context"
    Then I should see "Test Record"
    When I select "Account" context
    Then I should see "There are no accounts"
    When I select "Test Entity" context
    Then I should see "Test Record"
    And close ui dialog

  Scenario: Check if Custom entity is available in Email context and context switcher is operational
    When I click My Emails in user menu
    And I click "Compose"
    When I fill form with:
      | To      | test@local.com |
      | Subject | Test email     |
    And click "Send"
    Then I should see "The email was sent"
    And I click "view" on first row in grid
    And I click "Add Context"
    Then I should see "Test Record"
    When I select "Account" context
    Then I should see "There are no accounts"
    When I select "Test Entity" context
    Then I should see "Test Record"
    And close ui dialog
