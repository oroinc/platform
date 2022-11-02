@ticket-BAP-20370
@fixture-OroUserBundle:AdditionalUsersFixture.yml

Feature: Create Report with convert functions
  In order to manage reports
  As an Administrator
  I need to be able to create report shown records contains columns with applied convert functions

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Created report with one aggregation column filter
    Given I go to Reports & Segments / Manage Custom Reports
    When I click "Create Report"
    And fill "Report Form" with:
      | Name        | Test Report |
      | Entity      | User        |
      | Report Type | Table       |
    And add the following columns:
      | First name |             | First Name  |
      | Birthday   | Year        | Year        |
      | Birthday   | Month       | Month       |
      | Birthday   | Day         | Day         |
      | Birthday   | Day of year | Day Of Year |
    And add the following filters:
      | Field Condition    | First Name | starts with | P |
    And save and close form
    Then I should see "Report saved" flash message
    And there are 2 records in grid
    And sort grid by "First Name"
    And should see following grid:
      | First Name | Year | Month | Day | Day Of Year |
      | Patrick    |      |       |     |             |
      | Phil       | 2001 | 2     | 10  | 41          |

  Scenario: Edit report
    When I go to Reports & Segments / Manage Custom Reports
    Then I click "Edit" on row "Test Report" in grid
    And fill "Report Form" with:
      | Name        | Test Report update |
    And save and close form
    Then I should see "Report saved" flash message
    And should see following grid:
      | First Name | Year | Month | Day | Day Of Year |
      | Phil       | 2001 | 2     | 10  | 41          |
      | Patrick    |      |       |     |             |

  Scenario: Delete report
    When I go to Reports & Segments / Manage Custom Reports
    Then I click "delete" on row "Test Report update" in grid
    And I click "Yes, Delete"
    Then I should see "Item deleted" flash message
    And there is no records in grid
