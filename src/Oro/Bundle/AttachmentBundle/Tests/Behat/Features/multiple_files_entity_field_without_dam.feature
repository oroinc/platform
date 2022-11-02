@ticket-BB-18228
@regression

Feature: Multiple Files entity field without DAM
  In order to manage entity field configuration
  As an administrator
  I should be able to create entity field with type Multiple Files without Digital Asset Manager

  Scenario: Create Multiple Files entity field
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And I filter "Name" as is equal to "User"
    And I click view User in grid
    And I click "Create field"
    And I fill form with:
      | Field name    | custom_files   |
      | Storage type  | Table column   |
      | Type          | Multiple Files |
    And I click "Continue"
    And I should see "Allowed MIME Types" with options:
      | Value                                                                     |
      | text/csv                                                                  |
      | text/plain                                                                |
      | application/msword                                                        |
      | application/vnd.openxmlformats-officedocument.wordprocessingml.document   |
      | application/vnd.ms-excel                                                  |
      | application/vnd.openxmlformats-officedocument.spreadsheetml.sheet         |
      | application/vnd.ms-powerpoint                                             |
      | application/vnd.openxmlformats-officedocument.presentationml.presentation |
      | application/pdf                                                           |
      | application/zip                                                           |
      | image/gif                                                                 |
      | image/jpeg                                                                |
      | image/png                                                                 |
    And I fill form with:
      | Label                   | Custom Files                 |
      | File Size (MB)          | 10                           |
      | Allowed MIME types      | [application/pdf, image/png] |
      | Maximum Number Of Files | 2                            |
      | Use DAM                 | No                           |
      | File Applications       | [default]                    |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Validate mime type for attached files
    Given I go to System/ User Management/ Users
    And I shouldn't see "Custom Files" column in grid
    And I click Edit admin in grid
    When I fill "User Form With Multiple Files" with:
      | Custom File 1 | file1.txt |
    And I save and close form
    Then I should see "User Form With Multiple Files" validation errors:
      | Custom File 1 | The MIME type of the file is invalid ("text/plain"). Allowed MIME types are "application/pdf", "image/png". |

  Scenario: Validate sort order of files
    When I fill "User Form With Multiple Files" with:
      | Custom File Sort Order 1 | 9999999999 |
    Then I should see validation errors:
      | Custom File Sort Order 1 | This value should be between 0 and 2,147,483,647. |
    And I fill "User Form With Multiple Files" with:
      | Custom File Sort Order 1 | 1 |

  Scenario: Check maximum number of files
    Given I fill "User Form With Multiple Files" with:
      | Custom File 1 | example.pdf |
    When I click "Add File"
    Then I should not see "Add File"

  Scenario: Save user form with multiple files
    Given I fill "User Form With Multiple Files" with:
      | Custom File 2 | 300x300.png |
    When I save and close form
    Then I should see "User saved" flash message
    And I should see following grid:
      | Sort Order | File name   | Uploaded By |
      | 1          | example.pdf | John Doe    |
      | 2          | 300x300.png | John Doe    |

  Scenario: Change sort order
    Given I go to System/ User Management/ Users
    And I click Edit admin in grid
    And I fill "User Form With Multiple Files" with:
      | Custom File Sort Order 1 | 100 |
      | Custom File Sort Order 2 | 50  |
    When I save and close form
    Then I should see "User saved" flash message
    And I should see following grid:
      | Sort Order | File name   | Uploaded By |
      | 50         | 300x300.png | John Doe    |
      | 100        | example.pdf | John Doe    |
