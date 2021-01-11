@regression

Feature: Create report validations
  In order to prevent creating invalid reports
  As an Administrator
  I should not be able to create a report if not all required data are specified

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Should not be possible to create report without required fields
    Given I go to Reports & Segments / Manage Custom Reports
    When I click "Create Report"
    And I save and close form
    Then I should see validation errors:
      | Name        | This value should not be blank.  |
      | Entity      | This value should not be blank.  |
      | Report Type | This value should not be blank.  |

  Scenario: Should not be possible to create report without columns
    Given I fill "Report Form" with:
      | Name        | Test Report |
      | Entity      | User        |
      | Report Type | Table       |
    When I save and close form
    Then I should see "At least one column should be specified." error message
