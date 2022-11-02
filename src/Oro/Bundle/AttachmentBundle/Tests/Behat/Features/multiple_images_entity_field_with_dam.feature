@ticket-BB-18228
@regression

Feature: Multiple Images entity field with DAM
  In order to manage entity field configuration
  As an administrator
  I should be able to create entity field with type Multiple Images with Digital Asset Manager

  Scenario: Create Multiple Files entity field
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And I filter "Name" as is equal to "User"
    And I click view User in grid
    And I click "Create field"
    And I fill form with:
      | Field name    | custom_images   |
      | Storage type  | Table column   |
      | Type          | Multiple Images |
    And I click "Continue"
    And I fill form with:
      | Label                   | Custom Images |
      | File Size (MB)          | 10           |
      | Thumbnail Width         | 64           |
      | Thumbnail Height        | 64           |
      | Maximum Number Of Files | 3            |
      | Use DAM                 | Yes          |
      | File Applications       | [default]    |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Check maximum number of files
    Given I go to System/ User Management/ Users
    And I shouldn't see "Custom Images" column in grid
    And I click Edit admin in grid
    When I click "Add Image"
    And I click "Add Image"
    Then I should not see "Add Image"

  Scenario: Attach existing files and save user form
    Given I click "Choose Image 1"
    And I fill "Digital Asset Dialog Form" with:
      | File  | 300x300.png |
      | Title | 300x300 PNG |
    And I click "Upload"
    And I click on 300x300.png in grid
    And I click "Choose Image 2"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg   |
      | Title | Cat 1 JPEG |
    And I click "Upload"
    And I click on cat1.jpg in grid
    And I click "Choose Image 3"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg   |
      | Title | Cat 2 JPEG |
    And I click "Upload"
    And I click on cat2.jpg in grid
    When I save and close form
    Then I should see "User saved" flash message
    And I should see following grid:
      | Sort Order | Name        | Uploaded By |
      | 1          | 300x300.png | John Doe    |
      | 2          | cat1.jpg    | John Doe    |
      | 3          | cat2.jpg    | John Doe    |
