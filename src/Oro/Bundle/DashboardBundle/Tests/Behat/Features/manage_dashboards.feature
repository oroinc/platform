@ticket-BAP-11233
@automatically-ticket-tagged
@ticket-BAP-21510
Feature: Manage dashboards
  In order when system  has several dashboards
  As an administrator
  I need to have ability to manage dashboards

  Scenario: Create new dashboard
    Given I login as administrator
    And I go to Dashboards/Manage Dashboards
    And number of records should be 1
    And I click "Create Dashboard"
    And I fill form with:
      | Label      | My own custom dashboard |
      | Clone From | Blank Dashboard         |
    When I save and close form
    Then I should see "Dashboard saved" flash message
    And page has "My own custom dashboard" header

  Scenario: Menage dashboards
    Given I go to Dashboards/Manage Dashboards
    And number of records should be 2
    When I go to Dashboards/Dashboard
    Then page has "Dashboard" header

  Scenario: Update dashboard
    When I go to Dashboards/Manage Dashboards
    And I click edit "My own custom dashboard" in grid
    And I fill form with:
      | Label | My own custom dashboard update |
    And I save and close form
    Then I should see "Dashboard saved" flash message
    And page has "My own custom dashboard update" header

    When I go to Dashboards/Manage Dashboards
    Then I should see following grid containing rows:
      | Label                          |
      | My own custom dashboard update |
      | Dashboard                      |
