@regression
@skip
Feature: OpenAPI Specification Management
  In order to manage OpenAPI Specifications
  As Administrator
  I need to be able to view, create, edit and delete OpenAPI specification

  Scenario: Feature background
    Given I run Symfony "oro:api:doc:cache:clear --view=rest_json_api" command in "prod" environment

  Scenario: Create OpenAPI specification
    Given I login as administrator
    When I go to System/ Integrations/ OpenAPI Specifications
    And press "Request Specification"
    And fill form with:
      | Name        | Public Specification 1        |
      | Public Slug | specification-1               |
      | Format      | YAML                          |
      | API         | Back-Office API               |
      | Entities    | [Address Type, Business Unit] |
    Then I should see "JSON:API for back-office resources."
    When click "Add Server URL Button"
    And I type "invalid url" in "Server URL 1"
    And I press "Save and Close"
    Then I should see "This value is not a valid URL."
    When I type "http://example.com" in "Server URL 1"
    And I press "Save and Close"
    Then I should see "The request for creation of OpenAPI specification has been accepted for processing." flash message
    And I should see OpenAPI specification with:
      | Name         | Public Specification 1      |
      | Public Slug  | specification-1             |
      | Requested By | John Doe                    |
      | Format       | YAML                        |
      | API          | Back-Office API             |
      | Entities     | Address Type; Business Unit |
      | Server URLs  | http://example.com          |
    And I should see a "Creating Label" element
    When I wait for "Public Specification 1" OpenAPI specification status changed to "created"
    And I reload the page
    And I should see a "Created Label" element

  Scenario: Edit OpenAPI specification
    When I press "Edit"
    And fill form with:
      | Name        | Public Specification 2 |
      | Public Slug | specification-2        |
      | Format      | JSON                   |
    And press "Save and Close"
    Then I should see "OpenAPI specification saved" flash message
    And I should see OpenAPI specification with:
      | Name         | Public Specification 2      |
      | Public Slug  | specification-2             |
      | Requested By | John Doe                    |
      | Format       | JSON                        |
      | API          | Back-Office API             |
      | Entities     | Address Type; Business Unit |
    And I should see a "Renewing Label" element
    When I wait for "Public Specification 2" OpenAPI specification status changed to "created"
    And I reload the page
    And I should see a "Created Label" element

  Scenario: Create OpenAPI specification without public access
    Given I go to System/ Integrations/ OpenAPI Specifications
    When press "Request Specification"
    And fill form with:
      | Name   | Private Specification 3 |
      | Format | JSON (Pretty)           |
      | API    | Back-Office API         |
    When I press "Save and Close"
    Then I should see "The request for creation of OpenAPI specification has been accepted for processing." flash message
    And I should see OpenAPI specification with:
      | Name         | Private Specification 3 |
      | Public Slug  | N/A                     |
      | Requested By | John Doe                |
      | Format       | JSON (Pretty)           |
      | API          | Back-Office API         |
      | Entities     | All                     |
    And I should see a "Creating Label" element
    When I wait for "Private Specification 3" OpenAPI specification status changed to "created"
    And I reload the page
    And I should see a "Created Label" element

  Scenario: Publish OpenAPI specification
    Given I go to System/ Integrations/ OpenAPI Specifications
    When I click view "Public Specification 2" in grid
    And I click "Publish"
    Then I should see "The OpenAPI specification has been published. From now on, it cannot be changed and it is available to download without authorization." flash message
    And I should see a "Published Label" element

  Scenario: OpenAPI specification grid
    Given I go to System/ Integrations/ OpenAPI Specifications
    And I should see following grid:
      | Name                    | Public Slug     | API             | Format        | Status    |
      | Private Specification 3 |                 | Back-Office API | JSON (Pretty) | Created   |
      | Public Specification 2  | specification-2 | Back-Office API | JSON          | Published |
    When I check "Created" in Status filter
    Then I should see following grid:
      | Name                    | Public Slug | API             | Format        | Status  |
      | Private Specification 3 |             | Back-Office API | JSON (Pretty) | Created |
    When I check "Published" in Status filter
    Then I should see following grid:
      | Name                    | Public Slug     | API             | Format        | Status    |
      | Private Specification 3 |                 | Back-Office API | JSON (Pretty) | Created   |
      | Public Specification 2  | specification-2 | Back-Office API | JSON          | Published |
    When I reset Status filter
    And I check "Published" in Status filter
    Then I should see following grid:
      | Name                   | Public Slug     | API             | Format | Status    |
      | Public Specification 2 | specification-2 | Back-Office API | JSON   | Published |
