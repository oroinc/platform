@fixture-OroUserBundle:user.yml
@fixture-OroUserBundle:user_without_business_unit.yml

Feature: User search with business unit permissions
  In order to search
  As an user with role permissions 'View:Business Unit' for entity and without business unit
  I should not see this entity in search results

  Scenario: Search admin user with admin user
    Given I login as administrator
    And I click "Search"
    Then type "admin" in "search"
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type  | N | isSelected |
      | Users | 1 |            |
    And I should see following search results:
      | Title    | Type |
      | John Doe | User |

  Scenario: Edit view permissions for User entity with Sales Rep Role
    Given go to System / User Management / Roles
    And I click edit "Sales Rep" in grid
    And select following permissions:
      | User | View:Business Unit |
    And save and close form
    Then I should see "Role saved" flash message

  # Should be fixed in BAP-19537
  @skip
  Scenario: Search admin user with role permissions 'View:Business Unit'
    and with business that equals to owner of searching entity
    Given I login as "charlie" user
    And I click "Search"
    And type "admin" in "search"
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type  | N | isSelected |
      | Users | 1 |            |
    And I should see following search results:
      | Title    | Type |
      | John Doe | User |

  Scenario: Search admin user with role permissions 'View:Business Unit' for User entity and without business unit
    Given I login as "test" user
    And I click "Search"
    And type "admin" in "search"
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should not see following search entity types:
      | Type  |
      | Users |
