@regression
@ticket-BAP-15726
@ticket-BAP-21510
Feature: Extended table fields
  In order to manage data of extended fields
  As an Administrator
  I want to have possibility to create entity with extended fields

  Scenario: Create Custom Entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name         | customentity    |
      | Label        | Custom Entity   |
      | Plural Label | Custom Entities |
    And I save and close form
    Then I should see "Entity saved" flash message

  Scenario Outline: Create Custom Field Outline
    Given I click "Create Field"
    When I fill form with:
      | Field name | Custom<Type>Field |
      | Type       | <Type>            |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Type     |
      | BigInt   |
      | Boolean  |
      | Date     |
      | DateTime |
      | Decimal  |
      | Float    |
      | Integer  |
      | Money    |
      | Percent  |
      | SmallInt |
      | String   |
      | Text     |
      | WYSIWYG  |

  Scenario: Create Custom Field File
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomFileField |
      | Storage Type | Table column    |
      | Type         | File            |
    And click "Continue"
    And I fill form with:
      | File Size (MB) | 5 |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Custom Field Multiple Files
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomMultipleFileField |
      | Storage Type | Table column            |
      | Type         | Multiple Files          |
    And click "Continue"
    And I fill form with:
      | File Size (MB) | 5 |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Custom Field Image
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomImageField |
      | Storage Type | Table column     |
      | Type         | Image            |
    And click "Continue"
    And I fill form with:
      | File Size (MB)   | 5   |
      | Thumbnail Width  | 190 |
      | Thumbnail Height | 120 |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Custom Field Multiple Images
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomMultipleField |
      | Storage Type | Table column        |
      | Type         | Multiple Images     |
    And click "Continue"
    And I fill form with:
      | File Size (MB)   | 5   |
      | Thumbnail Width  | 190 |
      | Thumbnail Height | 120 |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Custom Field Select
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomSelectField |
      | Storage Type | Table column      |
      | Type         | Select            |
    And click "Continue"
    And set Options with:
      | Label   |
      | Option1 |
      | Option2 |
      | Option3 |
    And save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Custom Field Multi-Select
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomMultiSelectField |
      | Storage Type | Table column           |
      | Type         | Multi-Select           |
    And click "Continue"
    And set Options with:
      | Label   |
      | Option1 |
      | Option2 |
      | Option3 |
    And save and close form
    Then I should see "Field saved" flash message

  Scenario: Update schema
    When I click update schema
    Then I should see Schema updated flash message

  Scenario: Create a record for Custom entity
    When I go to System/Entities/Custom Entity
    And I click "Create Custom Entity"
    Then I fill "CustomEntityForm" with:
      | CustomBigIntField      | 1                              |
      | CustomIntegerField     | 1                              |
      | CustomBooleanField     | Yes                            |
      | CustomDateField        | <Date:Jul 28, 2021>            |
      | CustomDateTimeField    | <DateTime:2022-10-31 08:00:00> |
      | CustomDecimalField     | 1.0                            |
      | CustomFloatField       | 1.0                            |
      | CustomIntegerField     | 1                              |
      | CustomMoneyField       | 1                              |
      | CustomPercentField     | 1                              |
      | CustomSmallIntField    | 1                              |
      | CustomStringField      | String                         |
      | CustomTextField        | Text                           |
      | CustomMultiSelectField | [Option1, Option2]             |
      | CustomSelectField      | Option1                        |
      | CustomWYSIWYGField     | TWYSIWYG                       |
    And I save and close form
    And I should see "Entity saved" flash message

  Scenario: Edit a record for Custom entity
    When I click "Edit"
    And I fill "CustomEntityForm" with:
      | CustomBigIntField      | 2                              |
      | CustomIntegerField     | 2                              |
      | CustomBooleanField     | No                             |
      | CustomDateField        | <Date:Jul 18, 2021>            |
      | CustomDateTimeField    | <DateTime:2022-10-11 11:00:00> |
      | CustomDecimalField     | 2.0                            |
      | CustomFloatField       | 2.0                            |
      | CustomIntegerField     | 2                              |
      | CustomMoneyField       | 2                              |
      | CustomPercentField     | 2                              |
      | CustomSmallIntField    | 2                              |
      | CustomStringField      | String update                  |
      | CustomTextField        | Text update                    |
      | CustomMultiSelectField | [Option1, Option3]             |
      | CustomSelectField      | Option2                        |
      | CustomWYSIWYGField     | TWYSIWYG update                |
    And I save and close form
    Then I should see "Entity saved" flash message
