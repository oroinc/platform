@ticket-BAP-18091
@fixture-OroDataGridBundle:DisplayInGridEntities.yml
# load at least one report so that the Reports & Segments -> Manage Custom Reports page shows a grid
@fixture-OroConfigBundle:custom_report.yml
@regression

Feature: New Field Display in Grid (Platform)
  In order to make sure that main entity grids respect "Display in Grid" setting
  As an Administrator
  I want to add a new field to a configurable entity, mark "Display in Grid" and check that field is appears on grid

  Scenario: Login to Admin Panel
    Given I login as administrator

  Scenario Outline: Add new field and mark as Display in Grid
    Given I go to System/Entities/Entity Management
    And I filter Name as Is Equal To "<Name>"
    And I check "<Module>" in Module filter
    And I click View <Name> in grid
    And I click "Create field"
    And I fill form with:
      | Field Name   | <Field>      |
      | Storage Type | Table column |
      | Type         | String       |
    And I click "Continue"
    And I save and close form
    Examples:
      | Name                       | Field     | Module                 |
      | BusinessUnit               | TestField | OroOrganizationBundle  |
      | EmailTemplate              | TestField | OroEmailBundle         |
      | Group                      | TestField | OroUserBundle          |
      | Role                       | TestField | OroUserBundle          |
      | User                       | TestField | OroUserBundle          |
      | Report                     | TestField | OroReportBundle        |
#      | Localization               | TestField | OroLocaleBundle        | Error in BB-19125
      | DigitalAsset               | TestField | OroDigitalAssetBundle  |
      | EmailNotification          | TestField | OroNotificationBundle  |
      | EmbeddedForm               | TestField | OroEmbeddedFormBundle  |
      | Segment                    | TestField | OroSegmentBundle       |
      | Tag                        | TestField | OroTagBundle           |
      | Taxonomy                   | TestField | OroTagBundle           |

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario Outline: Check new field in grid settings
    Given I go to System/Entities/Entity Management
    And I filter Name as Is Equal To "<Name>"
    And I check "<Module>" in Module filter
    And I click View <Name> in grid
    And I click "Number of records"
    When click "Grid Settings"
    Then I should see following columns in the grid settings:
      | <Field> |
    Examples:
      | Name                       | Field     | Module                 |
      | BusinessUnit               | TestField | OroOrganizationBundle  |
      | EmailTemplate              | TestField | OroEmailBundle         |
      | Group                      | TestField | OroUserBundle          |
      | Role                       | TestField | OroUserBundle          |
      | User                       | TestField | OroUserBundle          |
#      | Localization               | TestField | OroLocaleBundle        | Error in BB-19125
      | DigitalAsset               | TestField | OroDigitalAssetBundle  |
      | EmailNotification          | TestField | OroNotificationBundle  |
      | EmbeddedForm               | TestField | OroEmbeddedFormBundle  |
      | Segment                    | TestField | OroSegmentBundle       |
      | Tag                        | TestField | OroTagBundle           |
      | Taxonomy                   | TestField | OroTagBundle           |

  Scenario: Check new field in Report grid settings
    Given I go to Reports & Segments/Manage Custom Reports
    When click "Grid Settings"
    Then I should see following columns in the grid settings:
      | TestField |
