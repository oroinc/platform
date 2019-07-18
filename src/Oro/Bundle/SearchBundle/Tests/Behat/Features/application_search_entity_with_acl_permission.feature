@fixture-OroUserBundle:user.yml
@fixture-OroCatalogBundle:categories.yml

Feature: Application search entity with acl permission
  In order to search entity with configured acl permission
  As a user
  I should see entity in search results depends on acl permission

  Scenario: Edit view permissions to 'Edit:None' for Category entity with Sales Rep Role
    Given I login as administrator
    Then go to System / User Management / Roles
    When I filter Label as is equal to "Sales Rep"
    And I click edit "Sales Rep" in grid
    And select following permissions:
      | Category | Edit:None |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Search Category with 'Edit:None' permissions
    Given I login as "charlie" user
    And I click "Search"
    And type "Printers" in "search"
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should not see following search entity types:
      | Type       |
      | Categories |

  Scenario: Edit view permissions to 'Edit:Global' for Category entity with Sales Rep Role
    Given I login as administrator
    Then go to System / User Management / Roles
    When I filter Label as is equal to "Sales Rep"
    And I click edit "Sales Rep" in grid
    And select following permissions:
      | Category | Edit:Global |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Search Category with 'Edit:Global' permissions
    Given I login as "charlie" user
    And I click "Search"
    And type "Printers" in "search"
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type           | N | isSelected |
      | Categories     | 1 |            |
    And I should see following search results:
      | Title    | Type     |
      | Printers | Category |

  Scenario: View entity from search results
    Given I follow "Printers"
    Then I should be on Category Update page
