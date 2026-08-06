@regression
@fixture-OroUserBundle:user.yml
Feature: User cancel button navigation
  In order to continue working from the place I came from
  As an Administrator
  I need the Cancel button on the user form to lead to the previously visited page

  Scenario: Cancel on the user edit form returns to the user view page
    Given I login as administrator
    And I go to System/User Management/Users
    And I filter Username as is equal to "charlie"
    When I click view charlie in grid
    And I wait for action
    And I click "Edit"
    And I click "Cancel"
    Then I should see user with:
      | Username | charlie |

  Scenario: Cancel on the user edit form returns to the users grid with the applied filter
    Given I go to System/User Management/Users
    And I filter Username as is equal to "charlie"
    And there is one record in grid
    When I click Edit charlie in grid
    And I click "Cancel"
    Then there is one record in grid
    And I should see "Charlie Sheen"
