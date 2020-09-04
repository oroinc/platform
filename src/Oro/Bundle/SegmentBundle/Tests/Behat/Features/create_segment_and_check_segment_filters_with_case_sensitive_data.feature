@ticket-BB-19429
@fixture-OroSegmentBundle:case_sensitive_segment.yml

Feature: Create segment and check segment filters with case insensitive data
  On order to be able to search for values ​​in filters without case insensitive
  As an administrator
  I create a segment and check if the filter search with case insensitive data

  Scenario: Create segment with segment filter
    Given I login as administrator
    And I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | User segment |
      | Entity       | User         |
      | Segment Type | Dynamic      |
    And I add the following columns:
      | id |
    And I add the following filters:
      | Apply segment | new SEGMENT |
    When I save form
    Then I should see "Segment saved" flash message
