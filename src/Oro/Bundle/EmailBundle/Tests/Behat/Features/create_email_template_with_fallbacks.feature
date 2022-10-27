@regression
@fixture-OroLocaleBundle:LocalizationFixture.yml
Feature: Create email template with fallbacks
  In order to send localized emails
  As an Administrator
  I want to have a possibility to configure fallbacks in localized email templates

  Scenario: Create email template for different localizations
    Given I login as administrator
    And I go to System/ Emails/ Templates
    And click "Create Email Template"
    And I fill "Email Template Form" with:
      | Owner         | John Doe        |
      | Template Name | Test Template   |
      | Type          | Html            |
      | Entity Name   | User            |
      | Subject       | Default subject |
      | Content       | Default content |
    When I click "Localization 1"
    Then I should see "Use Default Localization"
    And I fill "Email Template Form" with:
      | Subject Fallback | false                  |
      | Subject          | Localization 1 subject |
    When I click "Localization 2"
    Then I should see "Use Localization 1 (Parent localization)"
    And I fill "Email Template Form" with:
      | Content Fallback | false                  |
      | Content          | Localization 2 content |
    And click "Ellipsis button"
    When I click "Localization 3"
    Then I should see "Use Localization 1 (Parent localization)"
    And I fill "Email Template Form" with:
      | Subject Fallback | false                  |
      | Subject          | Localization 3 subject |
    When I save form
    Then I should see "Template saved" flash message

  Scenario: Fallback to default on send localized template
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), Localization1, Localization2, Localization3] |
      | Default Localization  | English (United States)                                                |
    And I save form
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | Localization1 |
    And I save form
    When I send email template "Test Template" to "admin"
    Then Email should contains the following:
      | To      | admin@example.com      |
      | Subject | Localization 1 subject |
      | Body    | Default content        |

  Scenario: Fallback to parent localization on send localized template
    Given I fill form with:
      | Default Localization | Localization2 |
    And I save form
    When I send email template "Test Template" to "admin"
    Then Email should contains the following:
      | To      | admin@example.com      |
      | Subject | Localization 1 subject |
      | Body    | Localization 2 content |

  Scenario: Recursive fallback to default on send localized template
    Given I fill form with:
      | Default Localization | Localization3 |
    And I save form
    When I send email template "Test Template" to "admin"
    Then Email should contains the following:
      | To      | admin@example.com      |
      | Subject | Localization 3 subject |
      | Body    | Default content        |

  Scenario: Template edit not break localization fallback
    Given I go to System/ Emails/ Templates
    And I filter Template name as is equal to "Test Template"
    And I click "Edit" on row "Test Template" in grid
    And I click "Localization 1"
    And I fill "Email Template Form" with:
      | Subject Fallback | false                      |
      | Subject          | New localization 1 subject |
      | Content Fallback | false                      |
      | Content          | New localization 1 content |
    When I save form
    Then I should see "Template saved" flash message
    When I send email template "Test Template" to "admin"
    Then Email should contains the following:
      | To      | admin@example.com          |
      | Subject | Localization 3 subject     |
      | Body    | New localization 1 content |
