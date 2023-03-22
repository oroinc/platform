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

  Scenario: Search actions in shortcut with special characters
    Given I reload the page
    And I click "Shortcuts"
    When I type "//.." in "Enter shortcut action"
    Then I should not see "There was an error performing the requested operation. Please try again or contact us for assistance." flash message
