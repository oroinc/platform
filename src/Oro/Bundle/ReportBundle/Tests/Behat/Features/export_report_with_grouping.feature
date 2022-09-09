@regression
@ticket-BAP-18931
@ticket-BAP-20488
@ticket-BAP-21539
@fixture-OroReportBundle:export_report_with_grouping.yml

Feature: Export Report with Grouping

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Check Report Datagrid
    When I go to Reports & Segments / Manage Custom Reports
    And I click View Total Users by Roles in grid
    Then I should see following grid containing rows:
      | Total Users | Role          |
      | 1           | Administrator |
      | 1           | Observer      |
      | 1           | Sales Manager |
      | 2           | Sales Rep     |

  Scenario: Export Report Datagrid
    When I click "Export Grid"
    And I click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    And exported file contains at least the following columns:
      | Total Users | Role          |
      | 1           | Administrator |
      | 1           | Observer      |
      | 1           | Sales Manager |
      | 2           | Sales Rep     |
