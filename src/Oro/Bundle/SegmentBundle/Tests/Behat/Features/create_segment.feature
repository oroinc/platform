@ticket-BB-11878

Feature: Create segment
  In order to manage segments
  As administrator
  I need to be able to create segments with different conditions

  Scenario Outline: Create segments with manual type
    Given I login as administrator
    And I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | <segment_name> |
      | Entity       | Segment        |
      | Segment Type | Manual         |
    And I add the following columns:
      | Id |
    When I save form
    Then I should see "Segment saved" flash message

    Examples:
      | segment_name |
      | Segment 1    |
      | Segment 2    |

  Scenario: Create manual type segment with two segments in filter
    Given I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment with two segments in filter |
      | Entity       | Segment                             |
      | Segment Type | Manual                              |
    And I add the following columns:
      | Id |
    And I add the following filters:
      | Apply segment | Segment 1 |
      | Apply segment | Segment 2 |
    When I save form
    Then I should see "Segment saved" flash message
    And I should see "Apply segment Segment 1"
    And I should see "Apply segment Segment 2"

  Scenario Outline: Check unique segments name validation
    Given I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | <segment_name> |
      | Entity       | Segment        |
      | Segment Type | Manual         |
    And I add the following columns:
      | Id |
    When I save form
    Then I should see "There is another segment with a similar name."

    Examples:
      | segment_name |
      | Segment 1    |
      | segmENT 1    |

  Scenario: Check Records Limit validation
    When I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Records Limit | 21474836479 |
    Then I should see validation errors:
      | Records Limit | This value should be between 0 and 2,147,483,647. |
