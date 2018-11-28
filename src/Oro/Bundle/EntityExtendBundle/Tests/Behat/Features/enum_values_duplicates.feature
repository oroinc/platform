Feature: Enum values duplicates
  In order to be able to create enum fields
  As a Administrator
  I should not be able to create enum field with duplicate options

  Scenario: Create enum field without duplicates
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click "Create Field"
    And I fill form with:
      | Field name | some_field |
      | Type       | Select     |
    And I click "Continue"
    And I fill form with:
      | Label | Custom Select |
    And set Options with:
      | Label    |
      | Option 1 |
      | Option 2 |
      | option 2 |
    And I save and close form
    And I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Create enum field with duplicates
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click "Create Field"
    And I fill form with:
      | Field name | some_field_duplicates |
      | Type       | Select                |
    And I click "Continue"
    And I fill form with:
      | Label | Custom Select |
    And set Options with:
      | Label                    |
      | Duplicate Option 1       |
      | Another Duplicate Option |
      | Duplicate Option 1       |
      | Another Duplicate Option |
      | Another Duplicate Option |
      | Another Option           |
    And I save and close form
    Then I should see "The options 'Duplicate Option 1', 'Another Duplicate Option' are duplicated."
    And I should not see "Update schema"
