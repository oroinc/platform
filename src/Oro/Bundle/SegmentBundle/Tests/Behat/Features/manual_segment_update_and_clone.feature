@ticket-BAP-21510

Feature: Manual segment update and clone
  In order to simplify the process of segment creation
  As an administrator
  I need to be able to create, edit and clone a segment

  Scenario: Create segment
    Given I login as administrator
    When I go to Reports & Segments/ Manage Segments
    Then there are two records in grid
    When I click "Create Segment"
    And I fill "Segment Form" with:
      | Name          | Test user segment             |
      | Description   | Test user segment description |
      | Entity        | User                          |
      | Segment Type  | Manual                        |
      | Records Limit | 100                           |

    And I add the following columns:
      | Id         |
      | First name |
    And I click "Edit First Segment Column"
    And I fill "Segment Form" with:
      | Sorting | Desc |
    And I click "Save Column Button"

    And I add the following filters:
      | Field Condition | First name | contains | John |
    And I save and close form
    Then I should see "Segment saved" flash message

  Scenario: View segment
    When I go to Reports & Segments/ Manage Segments
    Then there are 3 records in grid
    And I should see following grid containing rows:
      | Name              | Entity | Type   |
      | Test user segment | User   | Manual |
    And I click "View" on row "Test user segment" in grid
    And I should see following grid:
      | First name |
      | John       |

  Scenario: Update segment
    And I go to Reports & Segments / Manage Segments
    And I click edit "Test user segment" in grid
    And I fill "Segment Form" with:
      | Name | Updated test user segment |
    And I save and close form
    Then I should see "Segment saved" flash message

  Scenario: Clone segment from edit page
    Given I go to Reports & Segments/ Manage Segments
    When I click edit "Updated test user segment" in grid
    Then I should see an "Clone Segment Button" element
    When I click on "Clone Segment Button"
    Then Name field should has "Copy of Updated test user segment" value
    When I fill in "Name" with "Copy of Updated test user segment"
    And I save and close form
    Then I should see "Segment saved" flash message

  Scenario: View segments after Clone
    When I go to Reports & Segments/ Manage Segment
    Then I should see following grid containing rows:
      | Name                              | Entity | Type   |
      | Updated test user segment         | User   | Manual |
      | Copy of Updated test user segment | User   | Manual |
    And there are 4 records in grid
    And I click edit "Copy of Updated test user segment" in grid
    And Name field should has Copy of Updated test user segment value
    And Description field should has Test user segment description value
    And Entity field should has User value
    And Type field should has Manual value
    And Records Limit field should has 100 value
    And Owner field should has Main value
