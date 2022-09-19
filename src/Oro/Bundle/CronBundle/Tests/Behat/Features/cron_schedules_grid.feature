@ticket-BAP-21510
Feature: Cron schedules grid
  As an Administrator
  I need checking for the presence of a grid and non-empty rows in it

  Scenario: Checking for the presence of a grid and non-empty rows in it
    Given I login as administrator
    When I go to System/Scheduled Tasks
    Then I should see "CronSchedulesGrid" grid
    And I should see not empty grid
