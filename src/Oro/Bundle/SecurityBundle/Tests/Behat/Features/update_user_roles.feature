@fixture-OroUserBundle:users.yml
@fixture-OroUserBundle:UsersInFirstOrganizationFixture.yml

Feature: Update User Roles
  In order to control data available to user
  As an user
  I should see updated data when the list of roles updated for me

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Check users list with default roles list
    Given I operate as the User
    And I login as "charlie" user
    When I go to System/User Management/Users
    Then I should see following records in grid:
      | John    |
      | Charlie |
      | Megan   |
      | Main BU |
    And I should not see "Main Child BU"
    And I should not see "First BU"
    And I should not see "First Child BU"

  Scenario: Add administrator role to user
    Given I operate as the Admin
    And I login as administrator
    And I go to System/User Management/Users
    And I click edit Charlie in grid
    And I fill "User Form" with:
      | Roles | Administrator   |
    When I save and close form
    Then I should see "User saved" flash message

  Scenario: Check users list with updated roles list
    Given I operate as the User
    And I login as "charlie" user
    When I go to System/User Management/Users
    Then I should see following records in grid:
      | John           |
      | Charlie        |
      | Megan          |
      | Main BU        |
      | Main Child BU  |
      | First BU       |
      | First Child BU |
