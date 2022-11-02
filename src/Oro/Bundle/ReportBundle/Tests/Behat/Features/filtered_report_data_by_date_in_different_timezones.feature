@ticket-BB-20581
@regression
@fixture-OroReportBundle:filtered_report_data_by_date.yml

Feature: Filtered report data by date in different timezones
  In order to be able to filter report data in different time zones
  As an administrator
  I create a report with data and filter it by date in different time zones and check for data

  Scenario: Check data in default timezone
    Given I login as administrator
    When I go to Reports & Segments / Notes / Test Report
    And there are one record in grid

  Scenario: Check the data in the minimum allowable time zone (Pacific/Midway)
    Given I go to System / Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | (UTC -11:00) Pacific/Midway |
    And save form
    Then I should see "Configuration saved" flash message
    When I go to Reports & Segments / Notes / Test Report
    Then there are one record in grid

  Scenario: Check the data in the maximum allowable time zone (Pacific/Kiritimati)
    Given I go to System / Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Timezone | (UTC +14:00) Pacific/Kiritimati |
    And save form
    Then I should see "Configuration saved" flash message
    When I go to Reports & Segments / Notes / Test Report
    Then there are one record in grid
