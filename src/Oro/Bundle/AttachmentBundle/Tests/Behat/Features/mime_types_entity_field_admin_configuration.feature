@regression
@ticket-BB-15166

Feature: Mime types entity field admin configuration
  In order to manage entity field configuration
  As an administrator
  I should be able to set mime types for entity field which were allowed in global configuration

  Scenario: Create field for entity with file type
    Given I login as administrator
    Given I go to System/ Entities/ Entity Management
    And filter "Name" as is equal to "Product"
    And I click view Product in grid
    And I click "Create field"
    And I fill form with:
      | Field name    | FileType     |
      | Storage type  | Table column |
      | Type          | File         |
    And click "Continue"
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
      | File Size (MB)        | 10                           |
      | Allowed MIME types    | [application/pdf, image/png] |
    And I save form
    Then I should see "Field saved" flash message
