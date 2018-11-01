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

  Scenario: Create tags
    Given I login as administrator
    And I go to System / Tags Management / Tags
    And I click "Create Tag"
    And I fill form with:
      | Name        | Tag A      |
      | Taxonomy    | A Taxonomy |
    When I save and create new form
    Then I should see "Tag saved" flash message
    And I fill form with:
      | Name        | Tag B      |
      | Taxonomy    | B Taxonomy |
    When I save and close form
    Then I should see "Tag saved" flash message

    Scenario: Check tags on grid with sorting
      And I sort grid by "Taxonomy"
      And should see following grid:
        | Name  | Taxonomy   |
        | Tag A | A Taxonomy |
        | Tag B | B Taxonomy |
      And I sort grid by "Taxonomy"
      And should see following grid:
        | Name  | Taxonomy   |
        | Tag B | B Taxonomy |
        | Tag A | A Taxonomy |
