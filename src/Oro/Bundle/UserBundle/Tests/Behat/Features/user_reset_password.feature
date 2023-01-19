@ticket-BAP-21805
@fixture-OroUserBundle:user.yml

Feature: User reset password
  In order to manage own profile
  As an Administrator
  I should not be able to reset password for myself

  Scenario: Reset password action in grid
    Given I login as administrator
    When I go to System/User Management/Users
    Then I should not see following actions for admin in grid:
      | Reset password |
    And I should see following actions for charlie in grid:
      | Reset password |

  Scenario: Reset password action in my view page
    Given I login as administrator
    And I go to System/User Management/Users
    And I click view admin in grid
    When I follow "More actions"
    Then I should not see "Reset password"

  Scenario: Reset password action in other user view page
    Given I login as administrator
    And I go to System/User Management/Users
    And I click view charlie in grid
    When I follow "More actions"
    Then I should see "Reset password"

  Scenario: Reset password action in my profile page
    Given I login as administrator
    And I click My User in user menu
    When I follow "More actions"
    Then I should not see "Reset password"

  Scenario: Reset password mass action
    Given I login as administrator
    When I go to System/User Management/Users
    Then I should see following grid:
      | Username |
      | admin    |
      | charlie  |
    When I check all records in grid
    When I click "Reset password" link from mass action dropdown
    And I click "Reset" in confirmation dialogue
    Then I should see "Password reset completed successfully. 1 emails sent." flash message
