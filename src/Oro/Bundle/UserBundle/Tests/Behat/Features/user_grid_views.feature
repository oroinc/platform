@regression
@ticket-BAP-16903
@fixture-OroUserBundle:UsersGridViewsFeature.yml
Feature: User Grid Views
  In order to be able use defined grid view list
  As an Administrator
  I want to be sure that default and others pre-defined grid views works correctly

  Scenario: Page "Users" should open with default grid view
    Given I login as administrator
    And I go to System/User Management/Users
    Then I should see "Active Users"
    And I should see "Enabled Enabled"
    When I click "GridFiltersButton"
    Then I should see "Enabled: Enabled"

  Scenario: Check pre-defined grid views
    Given click grid view list
    Then I should see "Grid View All Users" element inside "GridViewList" element
    And I should see "Grid View Cannot Login" element inside "GridViewList" element
    And I should see "Grid View Disabled Users" element inside "GridViewList" element
    And click grid view list

  Scenario: Prepare data for "Cannot login" view
    Given I click "Reset password" on row "charlie@example.com" in grid
    And I confirm reset password
    Then I should see "Password reset request has been sent to charlie@example.com." flash message

  Scenario: Check view switch works correctly
    Given there is 2 records in grid
    Given I click on "All Users" in grid view list
    Then there is 3 records in grid
    When I click on "Cannot login" in grid view list
    Then I should see "Enabled: Enabled"
    And I should see "Password: is any of \"Reset"
    And there is one record in grid
    When I click on "Disabled Users" in grid view list
    Then I should see "Enabled: Disabled"
    And there is one record in grid
