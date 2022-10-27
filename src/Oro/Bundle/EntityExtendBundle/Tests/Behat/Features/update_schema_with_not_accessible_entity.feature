@regression
@ticket-BAP-17964

Feature: Update schema with not accessible entity
  In order to allow an administrator to keep not finished schema changes as is and apply schema for completed entities
  As an Administrator
  I want to update schema even if there are entities I did not finish

  Scenario: Background
    Given I login as administrator

  Scenario Outline: Create custom entities without fields
    Given I go to System/Entities/Entity Management
    When I click "Create Entity"
    And fill form with:
      | Name         | <entityName>  |
      | Label        | <entityLabel> |
      | Plural Label | <entityLabel> |
    And save and close form
    Then I should see "Entity saved" flash message

    Examples:
      | entityName     | entityLabel     |
      | EmptyEntity1   | Empty Entity 1  |
      | EmptyEntity2   | Empty Entity 2  |
      | EmptyEntity3   | Empty Entity 3  |
      | FinishedEntity | Finished Entity |

  Scenario Outline: Enable advanced options for custom entities
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "<entityName>"
    When I click Edit <entityName> in grid
    And check "Emails"
    And select "Yes" from "Enable Comments"
    And select "Yes" from "Enable Attachments"
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message

    Examples:
      | entityName   |
      | EmptyEntity2 |
      | EmptyEntity3 |

  Scenario: Remove custom entity
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "EmptyEntity3"
    When I click Remove EmptyEntity3 in grid
    And click "Yes" in confirmation dialogue
    Then I should see "Item deleted" flash message

  Scenario: Complete custom entity and update schema
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "FinishedEntity"
    When I click View FinishedEntity in grid
    And click "Create Field"
    And fill form with:
      | Field name | Name   |
      | Type       | String |
    And click "Continue"
    And save and close form
    Then I should see "Field saved" flash message
    And should see "Update Schema"
    When I click update schema
    Then I should see Schema updated flash message
