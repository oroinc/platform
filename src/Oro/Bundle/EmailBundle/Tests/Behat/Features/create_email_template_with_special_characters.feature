Feature: Create email template with special characters
  As Administrator

  Scenario: Use special chars in email template
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    And fill form with:
      | Template Name | Test Template |
      | Subject       | SampleSubject |
      | Content       | ľščťžýáíéúä   |
    And fill form with:
      | Type          | Plain Text  |
    When I save form
    Then I should see "Template saved" flash message
    When I click "Preview"
    Then I should see "ľščťžýáíéúä" inside "Preview Email" iframe
    And I close ui dialog
    And fill form with:
      | Type          | Html  |
    When I click "Preview"
    Then I should see "ľščťžýáíéúä" inside "Preview Email" iframe
    And I close ui dialog
    And fill form with:
      | Type          | Plain Text  |
    When I click "Preview"
    Then I should see "ľščťžýáíéúä" inside "Preview Email" iframe
    And I close ui dialog
