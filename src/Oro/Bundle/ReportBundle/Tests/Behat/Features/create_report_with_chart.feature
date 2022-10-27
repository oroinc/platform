@ticket-BAP-19786
@regression
Feature: Create Report with chart
  In order to manage reports
  As an Administrator
  I need to be able to create a report showing the records presented in a chart

  Scenario: Created report with chart
    Given I login as administrator
    And I go to Reports & Segments/Manage Custom Reports
    When I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Test Report |
      | Entity      | User        |
      | Report Type | Table       |
    And I add the following columns:
      | Login Count        |
      | Failed Login Count |
    And I fill "Report Form" with:
      | Chart Type        | Line Chart         |
      | Category (X axis) | Login Count        |
      | Value (Y axis)    | Failed Login Count |
    And I save and close form
    Then I should see "Report saved" flash message
    And should see following grid:
      | Login Count | Failed Login Count |
      | 1           | 0                  |
