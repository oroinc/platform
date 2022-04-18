@ticket-BB-18228
@regression

Feature: Multiple Images entity field without DAM
  In order to manage entity field configuration
  As an administrator
  I should be able to create entity field with type Multiple Images without Digital Asset Manager

  Scenario: Create Multiple Images entity field
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And I filter "Name" as is equal to "User"
    And I click view User in grid
    And I click "Create field"
    And I fill form with:
      | Field name    | custom_images   |
      | Storage type  | Table column    |
      | Type          | Multiple Images |
    And I click "Continue"
    And I should see "Allowed MIME Types" with options:
      | Value                    |
      | image/gif                |
      | image/jpeg               |
      | image/png                |
    And I fill form with:
      | Label                   | Custom Images |
      | File Size (MB)          | 10            |
      | Thumbnail Width         | 64            |
      | Thumbnail Height        | 64            |
      | Allowed MIME types      | [image/jpeg]   |
      | Maximum Number Of Files | 2             |
      | Use DAM                 | No            |
      | File Applications       | [default]     |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Validate mime type for attached files
    Given I go to System/ User Management/ Users
    And I shouldn't see "Custom Images" column in grid
    And I click Edit admin in grid
    When I fill "User Form With Multiple Images" with:
      | Custom Image 1 | 300x300.png |
    And I save and close form
    Then I should see "User Form With Multiple Images" validation errors:
      | Custom Image 1 | The MIME type of the file is invalid ("image/png"). Allowed MIME types are "image/jpeg". |

  Scenario: Check maximum number of files
    Given I fill "User Form With Multiple Images" with:
      | Custom Image 1 | cat1.jpg |
    When I click "Add Image"
    Then I should not see "Add Image"

  Scenario: Save user form with multiple files
    Given I fill "User Form With Multiple Images" with:
      | Custom Image 2 | cat2.jpg |
    When I save and close form
    Then I should see "User saved" flash message
    And I should see following grid:
      | Sort Order | Name     | Uploaded By |
      | 1          | cat1.jpg | John Doe    |
      | 2          | cat2.jpg | John Doe    |

  Scenario: Change sort order
    Given I go to System/ User Management/ Users
    And I click Edit admin in grid
    And I fill "User Form With Multiple Images" with:
      | Custom Image Sort Order 1 | 100 |
      | Custom Image Sort Order 2 | 50  |
    When I save and close form
    Then I should see "User saved" flash message
    And I should see following grid:
      | Sort Order | Name     | Uploaded By |
      | 50         | cat2.jpg | John Doe    |
      | 100        | cat1.jpg | John Doe    |
