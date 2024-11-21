@regression
@ticket-BAP-16169

Feature: Multi grid context with extend entity
  As Administrator user
  I need to have ability to get extend entity and use its records in Context for e.g. Tasks, Emails, etc.

  Scenario: Configure extend entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    When click Edit User in grid
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

  Scenario: Check if Extend entity is available in Task context and context switcher is operational
    When I go to Activities/Tasks
    And I click "Create Task"
    When I fill form with:
      | Subject | Test task |
    And I save and close form
    Then I should see "Task saved" flash message
    And I click "Add Context"
    When I select "Account" context
    Then I should see "There are no accounts"
    When I select "User" context
    Then I should see "There are no users"
    And close ui dialog

  Scenario: Check if Extend entity is available in Email context and context switcher is operational
    When I click My Emails in user menu
    And I click "Compose"
    When I fill form with:
      | To      | test@local.com |
      | Subject | Test email     |
    And click "Send"
    Then I should see "The email was sent" flash message
    And I click "view" on first row in grid
    And I click "Add Context"
    When I select "Account" context
    Then I should see "There are no accounts"
    And I select "User" context
    And close ui dialog
