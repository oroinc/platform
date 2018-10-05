@ticket-CRM-9055
@fixture-OroSalesBundle:OpportunityFixture.yml
Feature: Create Report with Dictionary field
  In order to manage reports
  As administrator
  I need to be able to create report with Dictionary field

  Scenario: Created report with Dictionary field
    Given I login as administrator
    And go to Sales/Opportunities
    And click Edit "Opportunity 1" in grid
    And I fill form with:
      | Close Reason | Cancelled |
    And I save and close form
    And I should see "Opportunity saved" flash message
    When I go to Reports & Segments / Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Test Dictionary field in Report |
      | Entity      | Opportunity                     |
      | Report Type | Table                           |
    And I add the following columns:
      | Opportunity name |
      | Close reason     |
    And I save and close form
    Then I should see "Report saved" flash message
    And I should see following grid:
      | Opportunity name | Close reason |
      | Opportunity 1    | Cancelled    |

