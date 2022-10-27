Feature: Create email template
  As Administrator
  I need to be able to create email template

  Scenario: Create email template
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    And fill form with:
      | Owner         | John Doe      |
      | Template Name | Test Template |
      | Type          | Html          |
      | Entity Name   | User          |
      | Subject       | SampleSubject |
      | Content       | SampleContent |
    When I save form
    Then I should see "Template saved" flash message
    When I click "Preview"
    Then I should see "SampleContent" inside "Preview Email" iframe
