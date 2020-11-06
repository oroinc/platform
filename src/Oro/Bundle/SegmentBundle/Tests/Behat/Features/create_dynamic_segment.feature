@ticket-BAP-20190
@ticket-BAP-15115
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
      | segment_name | filter_value     |
      | Segment 1    | s                |
      | Segment 2    | a                |
      | Segment 3    | NOT_EXISTING_STR |

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
      | Apply segment | Segment 1 |
      | Apply segment | Segment 2 |
    When I save form
    Then I should see "Segment saved" flash message
    And I should see "Apply segment Segment 1"
    And I should see "Apply segment Segment 2"
    And I go to Reports & Segments/ Manage Segments
    And click on Segment with two segments in filter in grid
    And I should see following grid:
      | First name |
      | Samantha   |

  Scenario: Create Dynamic type segment with two segments in filter
    Given I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment with two segments in filter and own filter |
      | Entity       | User                                               |
      | Segment Type | Dynamic                                            |
    And I add the following columns:
      | First name |
    And I add the following filters:
      | Field Condition | First name | contains | NOT_EXISTING_STR |
    And I add the following filters:
      | Apply segment | Segment 1 |
      | Apply segment | Segment 2 |
    When I save form
    Then I should see "Segment saved" flash message
    And I should see "Apply segment Segment 1"
    And I should see "Apply segment Segment 2"
    When I go to Reports & Segments/ Manage Segments
    And click on Segment with two segments in filter and own filter in grid
    Then there are no records in grid

  Scenario: Create Dynamic type segment with two segments in filter when filter is at the end
    Given I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment with two segments in filter and own filter at the end |
      | Entity       | User                                                          |
      | Segment Type | Dynamic                                                       |
    And I add the following columns:
      | First name |
    And I add the following filters:
      | Apply segment | Segment 1 |
      | Apply segment | Segment 2 |
    And I add the following filters:
      | Field Condition | First name | contains | NOT_EXISTING_STR |
    When I save form
    Then I should see "Segment saved" flash message
    And I should see "Apply segment Segment 1"
    And I should see "Apply segment Segment 2"
    When I go to Reports & Segments/ Manage Segments
    And click on Segment with two segments in filter and own filter at the end in grid
    Then there are no records in grid

  Scenario: Create Dynamic type segment with two segments in filter when filter is at the end
    Given I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment with two segments in filter and own filter at the end v2 |
      | Entity       | User                                                             |
      | Segment Type | Dynamic                                                          |
    And I add the following columns:
      | First name |
    And I add the following filters:
      | Apply segment | Segment 1 |
      | Apply segment | Segment 3 |
    And I add the following filters:
      | Field Condition | First name | contains | t |
    When I save form
    Then I should see "Segment saved" flash message
    And I should see "Apply segment Segment 1"
    And I should see "Apply segment Segment 3"
    When I go to Reports & Segments/ Manage Segments
    And click on Segment with two segments in filter and own filter at the end v2 in grid
    Then there are no records in grid
