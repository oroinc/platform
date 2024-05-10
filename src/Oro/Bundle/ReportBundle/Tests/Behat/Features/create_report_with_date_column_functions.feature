@regression
@ticket-BAP-22005
@fixture-OroReportBundle:users_with_birthdate.yml

Feature: Create report with date column functions
  In order to be able to create reports without validation errors

Scenario: Login to the back office
  Given I login as administrator

Scenario: Create report with empty grouping section
  Given I go to Reports & Segments / Manage Custom Reports
  When I click "Create Report"
  And fill "Report Form" with:
    | Name        | Test Report |
    | Entity      | User        |
    | Report Type | Table       |
  And add the following columns:
    | First name |                  | First Name  |
    | Birthday   | Year             | Year        |
    | Birthday   | Month            | Month       |
    | Birthday   | Day              | Day         |
    | Birthday   | Day of year      | Day Of Year |
  And save and close form

Scenario: Check report filters
  Given I go to Reports & Segments / Manage Custom Reports
  And I click View "Test Report" in grid
  Then I should see following grid:
    | First name  | Year   | Month  | Day  | Day of year  |
    | John        |        |        |      |              |
    | User 1      | 2000   | 1      | 1    | 1            |
    | User 2      | 2005   | 5      | 5    | 125          |
    | User 3      | 2010   | 10     | 10   | 283          |
  And number of records should be 4

  When I filter Year as equal "2000"
  Then records in grid should be 1
  When I reset Year filter
  Then there is 4 records in grid

  When I filter Month as equal "5"
  Then records in grid should be 1
  When I reset Month filter
  Then there is 4 records in grid

  When I filter Day as equal "1"
  Then records in grid should be 1
  When I reset Day filter
  Then there is 4 records in grid

  When I filter "Day of year" as equal "125"
  Then records in grid should be 1
  When I reset "Day of year" filter
  Then there is 4 records in grid
