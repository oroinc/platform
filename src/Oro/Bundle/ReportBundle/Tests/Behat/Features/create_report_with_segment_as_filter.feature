@ticket-BAP-16174
@fixture-OroUserBundle:user.yml
Feature: Create Report with Segment as filter
  In order to manage reports
  As administrator
  I need to be able to create report with Segment as filter

  Scenario: Created report with Segment as filter and fields with *-to-many relation should contain right row count
    Given I login as administrator
    # There are 2 users from fixtures assigned to default organization
    When I go to Reports & Segments / Manage Segments
    And I click "Create Segment"
    And I fill form with:
      | Name   | Test Organization Segment |
      | Entity | Organization              |
      | Type   | Dynamic                   |
    And I add the following columns:
      | Name |
    And I save and close form
    Then I should see "Segment saved" flash message
    When I go to Reports & Segments / Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Test Organization Report |
      | Entity      | Organization             |
      | Report Type | Table                    |
    And I add the following columns:
      | Name |
      | Users->First name |
    And I add the following filters:
      | Apply segment | Test Organization Segment |
    When I save and close form
    Then I should see "Report saved" flash message
    And there are two records in grid
