@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: Clone email template using not english default localization
  In order to create and update email templates
  As an Administrator
  I need to be able to clone email template using not english default localization settings

  Scenario: Feature Background
    Given I enable the existing localizations
    And I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Default Localization | Zulu_Loc |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Create email template
    Given I go to System/ Emails/ Templates
    When click "Create Email Template"
    And fill form with:
      | Owner         | John Doe      |
      | Template Name | Test Template |
      | Type          | Plain text    |
      | Entity Name   | Email         |
    And I click "English"
    And fill "Email Template Form" with:
      | Subject | English Order Confirmation Subject |
      | Content | English Order Confirmation Body    |
    And I click "Zulu"
    And fill "Email Template Form" with:
      | Subject | Zulu Order Confirmation Subject |
      | Content | Zulu Order Confirmation Body    |
    And I save and close form
    Then I should see "Template saved" flash message

  Scenario: Clone email template
    Given I filter Template Name as is equal to "Test Template"
    And I click "Clone" on first row in grid
    When fill form with:
      | Template Name | Cloned Template |
    And I save and close form
    Then I should see "Template saved" flash message

  Scenario: Check cloned email template
    Given I filter Template Name as is equal to "Cloned Template"
    When I click "Edit" on first row in grid
    Then "Email Template Form" must contains values:
      | Template Name | Cloned Template |

    When I click "English"
    Then "Email Template Form" must contains values:
      | Subject | English Order Confirmation Subject |
      | Content | English Order Confirmation Body    |

    When I click "Zulu"
    Then "Email Template Form" must contains values:
      | Subject | Zulu Order Confirmation Subject |
      | Content | Zulu Order Confirmation Body    |
