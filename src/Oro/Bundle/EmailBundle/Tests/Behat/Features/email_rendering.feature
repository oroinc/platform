@fixture-OroEmailBundle:bad-emails.yml
@fixture-OroEmailBundle:bad-emails-widget.yml
@ticket-BAP-15135
@automatically-ticket-tagged
Feature: Email rendering
  As a user
  I want to see only useful information from email

  Scenario: Dashboard widget
    Given I login as administrator
    When I go to Dashboards/Dashboard
    Then I should see text matching "Recent Emails"
    When I click "Unread Emails"
    And I should not see alert
    And I should not see malicious scripts

  Scenario: My emails
    When I click My Emails in user menu
    Then I should not see alert
    And I should not see malicious scripts

  Scenario: Thread view
    When I click My Emails in user menu
    And I click on Merry Christmas in grid
    Then I should not see alert
    And I should not see malicious scripts

  Scenario: Icon widget
    When I click on email notification icon
    Then I should not see alert
    And I should not see malicious scripts

  Scenario: Activity list
    When I go to System/User Management/Users
    And I click view John Doe in grid
    Then I should see "Merry Christmas" email in activity list
    When I collapse "Merry Christmas" in activity list
    Then I should not see alert
    And I should not see malicious scripts
