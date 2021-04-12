@regression
@ticket-BAP-18931
@ticket-BAP-20488
@fixture-OroUserBundle:users.yml

Feature: Report export with custom batch size
  In order to get grid data in CSV format
  As an administrator
  I should be able to export a grid containing any number of rows

  Scenario: Feature Background
    Given I login as administrator
    And I change the export batch size to 1

  Scenario: Create Report
    When I go to Reports & Segments / Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Test Report |
      | Entity      | User        |
      | Report Type | Table       |
    And I add the following columns:
      | Id       | Count |
      | Username | None  |
    And I add the following grouping columns:
      | Username |
    And I save and close form
    Then I should see "Report saved" flash message
    And I should see following grid containing rows:
      | Id | Username |
      | 1  | admin    |
      | 1  | charlie  |
      | 1  | megan    |

  Scenario: Export Report grid
    Given I click "Export Grid"
    And I click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    And exported file contains at least the following columns:
      | Id | Username |
      | 1  | admin    |
      | 1  | charlie  |
      | 1  | megan    |
