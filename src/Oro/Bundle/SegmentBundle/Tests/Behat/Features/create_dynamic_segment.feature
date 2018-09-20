@fixture-OroSegmentBundle:samantha_and_charlie_users.yml
Feature: Create dynamic segment
  In order to manage segments
  As administrator
  I need to be able to create dynamic segments with different conditions

  Scenario Outline: Create segments with Dynamic type
    Given I login as administrator
    And I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | <segment_name> |
      | Entity       | User           |
      | Segment Type | Dynamic        |
    And I add the following columns:
      | First name |
    And I add the following filters:
      | Field Condition | First name | contains | <filter_value> |
    When I save form
    Then I should see "Segment saved" flash message

    Examples:
      | segment_name | filter_value |
      | Segment 1    | s            |
      | Segment 2    | a            |

  Scenario: Create Dynamic type segment with two segments in filter
    Given I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment with two segments in filter |
      | Entity       | User                                |
      | Segment Type | Dynamic                             |
    And I add the following columns:
      | First name |
    And I add the following filters:
      | Field Condition | First name | contains | t |
    And I add the following filters:
      | Apply segment   | Segment 1  |
      | Apply segment   | Segment 2  |
    When I save form
    Then I should see "Segment saved" flash message
    And I should see "Apply segment Segment 1"
    And I should see "Apply segment Segment 2"
    And I go to Reports & Segments/ Manage Segments
    And click on Segment with two segments in filter in grid
    And I should see following grid:
      | First name |
      | Samantha   |
