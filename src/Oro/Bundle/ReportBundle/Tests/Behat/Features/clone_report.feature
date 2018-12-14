@ticket-BAP-17886
@fixture-OroUserBundle:AdditionalUsersFixture.yml
Feature: Clone report
  In order to simplify the process of report creation
  As an Administrator
  I need to be able to clone an existing report

  Scenario: Create report
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    When I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Report on clone |
      | Entity      | User            |
      | Report Type | Table           |
    And I add the following columns:
      | Id         | Count |
      | First name | None  |
    And I add the following grouping columns:
      | First name |
    And I add the following filters:
      | Field Condition | Username | contains | test |
    And I save and close form
    Then I should see "Report saved" flash message

  Scenario: Check clone button exist on grid
    When I go to Reports & Segments/ Manage Custom Reports
    Then should see following actions for Report on clone in grid:
      | Clone |

  Scenario: Clone report from view page
    Given I click view "Report on clone" in grid
    When I should see an "Clone Report Button" element
    And I click on "Clone Report Button"
    Then Name field should has "Copy of Report on clone" value
    When I save and close form
    Then I should see "Report saved" flash message
    And there are two records in grid

  Scenario: Clone report from edit page
    Given I go to Reports & Segments/ Manage Custom Reports
    When I click edit "Report on clone" in grid
    Then I should see an "Clone Report Button" element
    When I click on "Clone Report Button"
    Then Name field should has "Copy of Report on clone" value
    When I fill in "Name" with "Copy of Report on clone from edit page"
    And I save and close form
    Then I should see "Report saved" flash message
    And there are two records in grid

  Scenario: Check clone button is not exist on create page
    Given I go to Reports & Segments/ Manage Custom Reports
    When I click "Create Report"
    Then I should not see an "Clone Report Button" element

  Scenario: Clone report from grid
    Given I go to Reports & Segments/ Manage Custom Reports
    Then there are 3 records in grid
    When I click clone "Report on clone" in grid
    Then Name field should has "Copy of Report on clone" value
    When I fill in "Name" with "Copy of Report on clone from grid"
    And I save and close form
    Then I should see "Report saved" flash message
    And there are two records in grid

  Scenario: Check index page after clone
    Given I go to Reports & Segments/ Manage Custom Reports
    Then there are 4 records in grid
    And I should see "Copy of Report on clone" in grid
    And I should see "Copy of Report on clone from edit page" in grid
    And I should see "Copy of Report on clone from grid" in grid


