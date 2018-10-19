Feature: Create email template
  As Administrator
  I need to be able to create email template

  Scenario: Create email template
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    And fill form with:
      |Owner         | John Doe       |
      |Template Name | Test Template  |
      |Type          | Html           |
      |Entity Name   | Email          |
      |Subject       | <!DOCTYPE html><html><head></head><body>{% set option = 1 %}{% if option > 1 %}test{% endif %}</body></html> |
    When I save and close form
    Then I should see "Template saved" flash message
