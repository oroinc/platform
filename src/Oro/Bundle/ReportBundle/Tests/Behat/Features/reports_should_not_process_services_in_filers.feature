@fixture-OroReportBundle:user_with_service_value_in_first_name.yml
Feature: Reports should not process services in filers
    In order to manage reports
    As administrator
    I should not be able to call service from Symfony service container using Custom Report

    Scenario: Create report with the service call as filter value
        Given I login as administrator
        When I go to Reports & Segments / Manage Custom Reports
        And I click "Create Report"
        And I fill "Report Form" with:
          | Name        | Test Users Report |
          | Entity      | User              |
          | Report Type | Table             |
        And I add the following columns:
          | First name |
          | Last name  |
        And I add the following filters:
          | Field Condition | First name | is equal to | @testService->doWork() |
        When I save and close form
        Then I should see following grid:
          | First name             | Last name  |
          | @testService->doWork() | Test       |
