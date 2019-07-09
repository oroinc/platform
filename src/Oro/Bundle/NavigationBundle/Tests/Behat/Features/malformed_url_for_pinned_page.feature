@regression
@ticket-BB-18995
@fixture-OroNavigationBundle:MalformedUrlForPinnedPage.yml
Feature: Malformed Url for Pinned Page And History
  In order to have prevent UI hung
  As admin
  I need to be able to navigate UI even if there's a malformed UI was pinned or added to history due to truncation

  Scenario: Users grid is accessible and there is a pin in pin holder
    Given I login as administrator
    And there is malformed url for pinned tab
    When I go to System/User Management/Users
    Then Users link must be in pin holder
    And I should see following grid:
      | First name | Last name | Username |
      | John       | Doe       | admin    |

  Scenario: Error is shown after following malformed url from history
    Given History must looks like:
      | Users - User Management - System |
    When I click "Users - User Management - System"
    And I click on empty space
    Then I should see "Could not load malformed url" error message
    When I go to System/User Management/Users
    And I should see following grid:
      | First name | Last name | Username |
      | John       | Doe       | admin    |
