@ticket-BAP-15922
Feature: User session after logout

  Scenario: Restricted page's data is not accessible from cache after logout
    Given I login as administrator
    And I go to System/User Management/Users
    When reload the page
    When I click Logout in user menu
    Then I am on Login page
    When I move backward one page
    Then I should be on Login page
