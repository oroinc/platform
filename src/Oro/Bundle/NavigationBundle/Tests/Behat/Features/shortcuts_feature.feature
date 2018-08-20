@regression
@ticket-BAP-11235
@automatically-ticket-tagged
Feature: Shortcuts feature
  In order to decrease time for performing some commonly used tasks
  As a user
  I need to shortcuts functionality

  Scenario: Full list of shortcuts
    Given I login as administrator
    And I click "Shortcuts"
    And I follow "See full list"
    And I should be on Shortcut Actionslist page

  Scenario: Choose shortcut from search
    Given I click "Shortcuts"
    When I type "Create" in "Enter shortcut action"
    And click "Create new User" in shortcuts search results
    Then I should be on User Create page

  Scenario: Compose Email from shortcut
    Given I click "Shortcuts"
    When I type "Compose" in "Enter shortcut action"
    And click "Compose Email" in shortcuts search results
    Then I should see an "Email Form" element
