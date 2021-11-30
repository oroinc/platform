@ticket-BAP-17886
@fixture-OroUserBundle:AdditionalUsersFixture.yml
Feature: Clone segment
  In order to simplify the process of segment creation
  As an Administrator
  I need to be able to clone an existing segment

  Scenario: Create segment
    Given I login as administrator
    And I go to Reports & Segments/ Manage Segments
    And there are two records in grid
    When I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment on clone |
      | Entity       | User             |
      | Segment Type | Manual           |
    And I add the following columns:
      | First name    |
      | Last name     |
      | Primary Email |
    And I add the following filters:
      | Field Condition | Username | contains | test |
    And I save and close form
    Then I should see "Segment saved" flash message
    And there are 3 records in grid

  Scenario: Check clone button exist on grid
    When I go to Reports & Segments/ Manage Segments
    Then should see following actions for Segment on clone in grid:
      | Clone |

  Scenario: Clone segment from view page
    Given I click view "Segment on clone" in grid
    When I should see an "Clone Segment Button" element
    And I click on "Clone Segment Button"
    Then Name field should has "Copy of Segment on clone" value
    When I save and close form
    Then I should see "Segment saved" flash message
    And there are 3 records in grid

  Scenario: Clone segment from edit page
    Given I go to Reports & Segments/ Manage Segments
    When I click edit "Segment on clone" in grid
    Then I should see an "Clone Segment Button" element
    When I click on "Clone Segment Button"
    Then Name field should has "Copy of Segment on clone" value
    When I fill in "Name" with "Copy of Segment on clone from edit page"
    And I save and close form
    Then I should see "Segment saved" flash message
    And there are 3 records in grid

  Scenario: Check clone button is not exist on create page
    Given I go to Reports & Segments/ Manage Segments
    When I click "Create Segment"
    Then I should not see an "Clone Segment Button" element

  Scenario: Clone segment from grid
    Given I go to Reports & Segments/ Manage Segments
    And there are 5 records in grid
    When I click clone "Segment on clone" in grid
    Then Name field should has "Copy of Segment on clone" value
    When I fill in "Name" with "Copy of Segment on clone from grid"
    And I save and close form
    Then I should see "Segment saved" flash message
    And there are 3 records in grid

  Scenario: Check index page after clone
    Given I go to Reports & Segments/ Manage Segments
    Then there are 6 records in grid
    And I should see "Copy of Segment on clone" in grid
    And I should see "Copy of Segment on clone from edit page" in grid
    And I should see "Copy of Segment on clone from grid" in grid
