@ticket-BAP-12097

Feature: Create Report with grouping by date
  In order to manage reports
  As an Administrator
  I need to be able to create report shown records grouped by date

  Scenario: Created report with grouping by date
    Given I login as administrator
    When I go to Reports & Segments / Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Test Report |
      | Entity      | User        |
      | Report Type | Table       |
    And I add the following columns:
      | Id            |
      | Username      |
      | Primary Email |
    And I check "Enable grouping by date"
    And I save and close form
    Then I should see "The date grouping filter requires configuring a grouping column." error message
    When I add the following grouping columns:
      | Id |
    And I save and close form
    Then I should see "Specify a field on which date grouping filter can be applied." error message
    When I select "Created At" from date grouping field
    And I save and close form
    Then I should see "Report saved" flash message
