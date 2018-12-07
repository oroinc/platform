@regression
@ticket-BAP-17951

Feature: Entity select fields
  In order to allows users to chose single or multiple value from a list of predetermined options
  As an Administrator
  I want to create a select or multi-select entity field and define the list of its options

  Scenario: Feature Background
    Given I login as administrator
    And go to System/Entities/Entity Management

  Scenario: Can create Select field
    Given I filter Name as is equal to "User"
    And click View User in grid
    And click "Create Field"
    And fill form with:
      | Field name | selectField |
      | Type       | Select      |
    And click "Continue"
    And fill form with:
      | Label | Select Field |
    And set Options with:
      | Label    |
      | Option 1 |
      | Option 2 |
      | option 2 |
    And save and close form
    And should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Can create Multi-Select field
    Given I filter Name as is equal to "User"
    And click View User in grid
    And click "Create Field"
    And fill form with:
      | Field name | multiSelectField |
      | Type       | Multi-Select     |
    And click "Continue"
    And fill form with:
      | Label | Multi Select Field |
    And set Options with:
      | Label    |
      | Option 1 |
      | Option 2 |
      | option 2 |
    And save and close form
    And should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Cannot create Select field if there are several options with the same label
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And click "Create Field"
    And fill form with:
      | Field name | selectDuplicates |
      | Type       | Select           |
    And click "Continue"
    And set Options with:
      | Label                    |
      | Duplicate Option 1       |
      | Another Duplicate Option |
      | Duplicate Option 1       |
      | Another Duplicate Option |
      | Another Duplicate Option |
      | Another Option           |
    When I save and close form
    Then I should see "The options 'Duplicate Option 1', 'Another Duplicate Option' are duplicated."
    And should not see "Update schema"

  Scenario: Cannot create Multi-Select field if there are several options with the same label
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And click "Create Field"
    And fill form with:
      | Field name | multiSelectDuplicates |
      | Type       | Multi-Select          |
    And click "Continue"
    And set Options with:
      | Label                    |
      | Duplicate Option 1       |
      | Another Duplicate Option |
      | Duplicate Option 1       |
      | Another Duplicate Option |
      | Another Duplicate Option |
      | Another Option           |
    When I save and close form
    Then I should see "The options 'Duplicate Option 1', 'Another Duplicate Option' are duplicated."
    And should not see "Update schema"
