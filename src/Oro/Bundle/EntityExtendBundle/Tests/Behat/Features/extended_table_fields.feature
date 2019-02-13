@regression
@ticket-BAP-15726
Feature: Extended table fields
  In order to manage data of extended fields
  As an Administrator
  I want to have possibility to create entity with extended fields

  Scenario: Create Custom Entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name         | customentity     |
      | Label        | Custom Entity   |
      | Plural Label | Custom Entities |
    And I save and close form
    Then I should see "Entity saved" flash message

  Scenario: Create Integer Custom Field
    Given I click "Create Field"
    When I fill form with:
      | Field name | CustomIntegerField |
      | Type       | Integer    |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"
    When I click update schema
    And I should see Schema updated flash message

  Scenario: Create a record for Custom entity
    When I go to System/Entities/Custom Entity
    And I click "Create Custom Entity"
    When I fill form with:
      | CustomIntegerField | -1 |
    And I save and close form
    Then I should see "Entity saved" flash message
    When I click "Edit"
    And I fill form with:
      | CustomIntegerField | 1 |
    And I save and close form
    Then I should see "Entity saved" flash message
