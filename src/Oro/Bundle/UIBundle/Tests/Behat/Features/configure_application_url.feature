@ticket-BAP-12990
@automatically-ticket-tagged
Feature: Configure application URL
  In order to configure application URL
  As a Site Administrator
  I want to be able to change it in application settings

  Scenario: Set invalid application URL
    Given I login as administrator
    And I go to System/Configuration
    When I fill "Application Settings Form" with:
      | Application URL | no-proper-url-value |
    And I click "Save settings"
    Then I should see "This value is not a valid URL."

  Scenario: Set empty application URL
    Given I fill "Application Settings Form" with:
      | Application URL |  |
    And I click "Save settings"
    Then I should see "This value should not be blank."

  Scenario: Set valid application URL
    Given I fill "Application Settings Form" with:
      | Application URL | http://dev-commerce.local/ |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
