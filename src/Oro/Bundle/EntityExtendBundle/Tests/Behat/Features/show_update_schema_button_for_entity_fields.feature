Feature: Show "Update Schema" button for entity fields
  In order to find out that schema update for custom entity fields is required, and be able to do it
  As a Site Administrator
  I want to see "Update Schema" button at custom entity screen

  Scenario: Create Custom Entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name  | testcustomentity           |
      | Label | Test Custom Entity           |
      | Plural Label  | Test Custom Entities |
    And I save and close form
    Then I should see "Entity saved" flash message
    And I should not see "Update Schema"

  Scenario: Create Custom Field
    Given I click "Create Field"
    When I fill form with:
      | Field name  | some_field |
      | Type        | Decimal    |
    And I press "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"

  Scenario: Create another one Custom Field
    Given I click "Create Field"
    When I fill form with:
      | Field name  | some_another_field |
      | Type        | Boolean    |
    And I press "Continue"
    When I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"

  Scenario: Remove one Custom Field and continue to see "Update Schema" button
    Given I click Remove some_another_field in grid
    Then I should see "Delete Confirmation"
    And I press "Yes"
    Then I should see some_another_field Boolean some_another_field Custom Deleted in grid
    And I should see "Update Schema"

  Scenario: Update schema with help of "Update Schema" button
    Given I click "Update Schema"
    Then I should see "Schema update confirmation"
    And I press "Yes, Proceed"
    Then I should see "Schema updated"

  Scenario: Restore field and continue to see "Update Schema" button, after deletion of another field
    Given I move backward one page
    Then I click Restore some_another_field in grid
    And I should see some_another_field Boolean some_another_field Custom Restored in grid
    And I should see "Update Schema"
    Then I click Remove some_field in grid
    Then I should see "Delete Confirmation"
    And I press "Yes"
    Then I should see some_field Decimal some_field Custom Deleted in grid
    And I should see "Update Schema"
