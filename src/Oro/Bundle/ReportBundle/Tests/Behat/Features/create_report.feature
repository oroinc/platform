Feature: Create report
  In order to manage reports
  As administrator
  I need to be able to create report

  Scenario: Success create with filter "is empty"
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    And I press "Create Report"
    And I fill "Report Form" with:
      | Name        | Accounts Report |
      | Entity      | Account         |
      | Report Type | Table           |
    And I add the following columns:
      | Account name |
    And I add the following filters:
      | Field Condition | Id | is empty |
    When I save form
    Then I should see "Report saved" flash message
