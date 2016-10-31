@fixture-searchEntities.yml
Feature: Application search
  In order to decrease time for search some common entities
  As a user
  I need to search functionality

  Scenario: Search all
    Given I login as administrator
    And I follow "Search"
    And type "Common" in "search"
    And I should see 3 search suggestions
    When I press "Go"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type            | N | isSelected |
      | All             | 3 | yes        |
      | Business Units  | 1 |            |
      | Organizations   | 1 |            |
      | Reports         | 1 |            |
    And number of records should be 3
    And I should see following search results:
      | Title                | Type          |
      | Common Organization  | Organization  |
      | Common Report        | Report        |
      | Common Business Unit | Business Unit |

  Scenario: Search by Business Units
    Given I follow "Search"
    And I select "Business Unit" from search types
    And I type "Common" in "search"
    And I should see 1 search suggestion
    When I press "Go"
    Then I should see following search entity types:
      | Type            | N | isSelected |
      | All             | 3 |            |
      | Business Units  | 1 | yes        |
      | Organizations   | 1 |            |
      | Reports         | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title                | Type          |
      | Common Business Unit | Business Unit |

  Scenario: View entity from search results
    Given I follow "Common Business Unit"
    Then I should be on Business Unit View page

  Scenario: View entity from search suggestion
    Given I follow "Search"
    And I select "All" from search types
    And I type "John" in "search"
    And I should see 1 search suggestion
    And I follow "admin"
    And I should be on User View page

  Scenario: No results search
    Given I follow "Search"
    And I type "This line does not make sense" in "search"
    When I press "Go"
    Then I should see "No results were found to match your search."
    And I should see "Try modifying your search criteria or creating a new"
