Feature: Create report
  In order to manage orders
  As administrator
  I need to be able to create report

  Scenario: Success create with is empty filter
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    And I press "Create Report"
    And I fill out the report form with the following data:
      | Name                | Entity      | Report Type |
      | Opportunity budget  | Opportunity | Table       |
    And I add the following columns:
      | Columns                           |
      | Opportunity / Customer > Name     |
      | Opportunity / Customer > ID       |
      | Opportunity > Base Budget Amount  |
    And I press "Save And Close" button
