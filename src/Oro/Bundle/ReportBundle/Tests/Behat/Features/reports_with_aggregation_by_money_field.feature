@ticket-BB-20775
@regression

Feature: Reports with aggregation by money field
  Aggregating money fields must be rendered with correct formatting for money values (maximum precision is equal 4)

  Scenario: Create money field
    Given I login as administrator
    And go to System/ Entities/ Entity Management
    And filter Name as is equal to "Group"
    And click view Group in grid
    When I click "Create Field"
    And fill form with:
      | Field name   | MoneyField   |
      | Storage Type | Table column |
      | Type         | Money        |
    And click "Continue"
    And save and close form
    Then I should see "Field saved" flash message
    And should see "Update Schema"
    When I click "Update schema"
    And click "Yes, Proceed"
    Then I should see Schema updated flash message

  Scenario Outline: Scenario: Update 'Money' field
    Given I go to System/ User Management/ Groups
    And click edit <Group> in grid
    When I fill form with:
      | MoneyField | <Money> |
    And save and close form
    Then I should see "Group saved" flash message
    Examples:
      | Group          | Money      |
      | Administrators | 19574.1231 |
      | Marketing      | 77424.5677 |
      | Sales          | 86243.8534 |

  Scenario: Create report and check that the money fields are formatted correctly
    Given I go to Reports & Segments/ Manage Custom Reports
    And click "Create Report"
    When I fill "Report Form" with:
      | Name        | ReportWithAVGFunction |
      | Entity      | Group                 |
      | Report Type | Table                 |
    And add the following columns:
      | MoneyField | Average |
      | Owner      | None    |
    And add the following grouping columns:
      | Owner |
    And save and close form
    Then I should see "Report saved" flash message
    And should see following grid:
      | MoneyField  | Owner |
      | 61,080.8467 | Main  |
