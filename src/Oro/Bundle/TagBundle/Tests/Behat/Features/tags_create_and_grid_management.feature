@ticket-BAP-21510
@ticket-BAP-15034
Feature: Tags create and grid management
  In order to manage tags
  As administrator
  I need to be able to create tag and view it on grid

  Scenario: Create taxonomies
    Given I login as administrator
    And I go to System / Tags Management / Taxonomies
    And I click "Create Taxonomy"
    And I fill form with:
      | Name        | A Taxonomy |
    When I save form
    Then I should see "Taxonomy has been saved" flash message
    And I go to System / Tags Management / Taxonomies
    And I click "Create Taxonomy"
    And I fill form with:
      | Name        | B Taxonomy |
    When I save and close form
    Then I should see "Taxonomy has been saved" flash message

  Scenario: Checking for empty Tag grid and Create tags
    And I go to System / Tags Management / Tags
    And there is no records in grid
    And I click "Create Tag"
    And I fill form with:
      | Name        | TagA       |
      | Taxonomy    | A Taxonomy |
    When I save and create new form
    Then I should see "Tag saved" flash message
    And I fill form with:
      | Name        | TagB       |
      | Taxonomy    | B Taxonomy |
    When I save and close form
    Then I should see "Tag saved" flash message

    Scenario: Check tags on grid with sorting
      And I sort grid by "Taxonomy"
      And should see following grid:
        | Name | Taxonomy   |
        | TagA | A Taxonomy |
        | TagB | B Taxonomy |
      And I sort grid by "Taxonomy"
      And should see following grid:
        | Name | Taxonomy   |
        | TagB | B Taxonomy |
        | TagA | A Taxonomy |

  Scenario: Check update, filter and search tags on grid
    When I click "Edit" on row "TagA" in grid
    And I fill form with:
      | Name | TagC |
    Then I save and close form
    And I should see "Tag saved" flash message
    And should see following grid:
      | Name | Taxonomy   |
      | TagB | B Taxonomy |
      | TagC | A Taxonomy |

    And filter Name as is equal to "TagB"
    And should see following grid:
      | Name | Taxonomy   |
      | TagB | B Taxonomy |
    And I click "Search by tag" on row "TagB" in grid
    And I should see "No results were found"
