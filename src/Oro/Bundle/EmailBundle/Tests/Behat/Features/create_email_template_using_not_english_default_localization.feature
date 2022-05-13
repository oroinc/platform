@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroEmailBundle:templates.yml
Feature: Create email template using not english default localization
  In order to create and update email templates
  As an Administrator
  I need to be able to create and update email template using not english default localization settings

  Scenario: Feature Background
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [Zulu_Loc] |
      | Default Localization  | Zulu_Loc   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Create email template
    Given I go to System/ Emails/ Templates
    When click "Create Email Template"
    And fill form with:
      |Owner         | John Doe       |
      |Template Name | Test Template  |
      |Type          | Html           |
      |Entity Name   | User           |
      |Subject       | Test subject   |
      |Content       | Test content   |
    And I save and close form
    Then I should see "Template saved" flash message

  Scenario: Update email template
    Given I click "Edit" on first row in grid
    And fill form with:
      |Subject       | Test subject edited |
      |Content       | Test content edited |
    And I save and close form
    Then I should see "Template saved" flash message
