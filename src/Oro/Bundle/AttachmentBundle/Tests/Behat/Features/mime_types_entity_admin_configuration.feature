@regression
@ticket-BB-15166

Feature: Mime types entity admin configuration
  In order to manage entity configuration
  As an administrator
  I should be able to set mime types for entity which were allowed in global configuration

  Scenario: Update mime types configuration for entity
    Given I login as administrator
    Given I go to System/ Entities/ Entity Management
    And filter "Name" as is equal to "Product"
    And I click "Edit"
    And I should see "Allowed MIME Types" with options:
      | Value                    |
      | application/pdf          |
      | application/vnd.ms-excel |
      | application/msword       |
      | application/zip          |
      | image/gif                |
      | image/jpeg               |
      | image/png                |
    And I fill form with:
      | Allowed MIME types | [application/pdf, image/png] |
    And I submit form
    Then I should see "Entity saved" flash message
