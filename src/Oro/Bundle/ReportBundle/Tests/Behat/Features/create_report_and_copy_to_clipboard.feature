@regression
@ticket-BAP-17694
Feature: Create report and copy to clipboard
  In order to manage reports result
  As administrator
  I need to be able to create report and copy sql query result to clipboard

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    When fill "System Config Form" with:
      | Display SQL in Reports and Segments | true |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Copy sql query result to clipboard
    Given I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Organization Report |
      | Entity      | Organization        |
      | Report Type | Table               |
    And I add the following columns:
      | Name |
    And I save and close form
    And I should see "Report saved" flash message
    When I click "Show SQL Query"
    And I click "Copy to Clipboard"
    Then I should see "Copied to Clipboard" flash message
