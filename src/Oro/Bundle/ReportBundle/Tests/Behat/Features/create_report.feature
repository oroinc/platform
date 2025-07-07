@regression
@ticket-BAP-14559
@automatically-ticket-tagged
Feature: Create report
  In order to manage reports
  As administrator
  I need to be able to create report

  Scenario: Success create with filter "is empty" and boolean filter "Yes"
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Contacts Report |
      | Entity      | User Emails     |
      | Report Type | Table           |
    And I add the following columns:
      | Subject |
    And I add the following filters:
      | Field Condition | Private | Yes      |
      | Field Condition | Id      | is empty |
    When I save form
    Then I should see "Report saved" flash message
    And I should see "field value is Yes"
    And I should not see "field value is Select Value"
