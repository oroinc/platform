@ticket-BAP-20368
@fixture-OroUserBundle:AdditionalUsersFixture.yml

Feature: Create Report with aggregation column filter
  In order to manage reports
  As an Administrator
  I need to be able to create report shown records filtered by aggregation columns

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Created report with one aggregation column filter
    Given I go to Reports & Segments / Manage Custom Reports
    When I click "Create Report"
    And fill "Report Form" with:
      | Name        | Test Report |
      | Entity      | User        |
      | Report Type | Table       |
    And add the following columns:
      | First name |       | First Name |
      | Id         | Count | User Count |
    And add the following grouping columns:
      | First name |
    And add the following filters:
      | Field Condition    | First Name | is not empty |   |
      | Aggregation column | User Count | greater than | 1 |
    And save and close form
    Then I should see "Report saved" flash message
    And there are 1 records in grid
    And should see following grid:
      | First Name | User Count |
      | Max        | 2          |

  Scenario: Created report with several aggregation column filters
    Given I go to Reports & Segments / Manage Custom Reports
    When I click "Create Report"
    And fill "Report Form" with:
      | Name        | Test Report 2 |
      | Entity      | User          |
      | Report Type | Table         |
    And add the following columns:
      | First name  |       | First Name        |
      | Id          | Count | User Count        |
      | Login Count | Sum   | Total Login Count |
    And add the following grouping columns:
      | First name |
    And add the following filters:
      | Field Condition    | First Name        | is not empty        |   |
      | Aggregation column | Total Login Count | less than           | 1 |
      | Aggregation column | User Count        | equals or less than | 1 |
    And save and close form
    Then I should see "Report saved" flash message
    And there are 2 records in grid
    And sort grid by "First Name"
    And should see following grid:
      | First Name | User Count | Total Login Count |
      | Patrick    | 1          | 0                 |
      | Phil       | 1          | 0                 |
