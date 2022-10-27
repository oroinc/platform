@regression
@ticket-BAP-18940
@fixture-OroLocaleBundle:date_cells_in_datagrids_are_formatted_with_timezone.yml

Feature: Date cells in datagrids are formatted with timezone
  In order to use custom reports
  As a back office user
  I need to see the dates in the correct time zone when grouping by date in reports

  Scenario: Prepare order and set time zone
    Given I login as administrator
    And I have a complete calendar date table from "2018" to "2019"
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | Australia/Melbourne |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create report and check date field for proper formatting with time zone
    Given I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    When I fill form with:
      | Name        | Test Report   |
      | Entity      | Activity list |
      | Report Type | Table         |
    And I add the following columns:
      | Id |
    And I add the following grouping columns:
      | Id |
    And I fill form with:
      | Enable grouping by date         | true |
      | Allow To Skip Empty Time Period | true |
    And I select "Created At" from date grouping field
    And I save and close form
    Then I should see "Report saved" flash message
    And I filter Time Period as between "Jan 1, 2019 11:30 AM" and "Jan 3, 2020 11:30 AM"
    And there is 1 record in grid
    And I should see following grid:
      | Time period | Id |
      | 31-5-2019   | 1  |
