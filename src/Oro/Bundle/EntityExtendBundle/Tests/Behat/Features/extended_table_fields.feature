@regression
@ticket-BAP-15726
@ticket-BAP-21510

Feature: Extended table fields
  In order to manage data of extended fields
  As an Administrator
  I want to have possibility to create entity with extended fields
  I want to see "Update Schema" button at custom entity screen

  Scenario: View User Entity Management
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    Then I click View User in grid

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
    And click "Add"
    And click "Add"
    And click "Add"
    And I fill "Entity Config Form" with:
      | Option First  | Option1 |
      | Option Second | Option2 |
      | Option Third  | Option3 |
    And save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Custom Field Multi-Select
    When I click "Create Field"
    And I fill form with:
      | Field Name   | CustomMultiSelectField |
      | Storage Type | Table column           |
      | Type         | Multi-Select           |
    And click "Continue"
    And click "Add"
    And click "Add"
    And click "Add"
    And I fill "Entity Config Form" with:
      | Option First  | Option1 |
      | Option Second | Option2 |
      | Option Third  | Option3 |
    And save and close form
    Then I should see "Field saved" flash message

  Scenario: Update schema
    When I click update schema
    Then I should see Schema updated flash message
