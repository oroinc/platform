@ticket-BB-18410
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml

Feature: Create Report with Country Field
  In order to manage reports
  As administrator
  I need to be able to create report with country field and have it properly localizable

  Scenario: Feature Background
    Given I login as administrator
    And I enable the existing localizations

  Scenario: Create report with Country field
    Given I go to Reports & Segments / Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Test Country Field in Report |
      | Entity      | Order                        |
      | Report Type | Table                        |
    And I add the following columns:
      | ID                            |
      | Billing Address->Country name |
    When I save and close form
    Then I should see "Report saved" flash message
    And there is one record in grid
    And I should see following grid:
      | Id | Country Name  |
      | 1  | United States |

  Scenario: Switch to another localization
    Given I go to System/Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill form with:
      | Default Localization | Zulu_Loc |
    When submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check that country name is translated in report grid
    Given I go to Reports & Segments / Manage Custom Reports
    When click View "Test Country Field in Report" in grid
    Then I should see following grid:
      | Id | Country Name      |
      | 1  | United StatesZulu |
    When I filter "Id" as equals "1"
    Then I should see following grid:
      | Id | Country Name      |
      | 1  | United StatesZulu |
    When I filter "Country name" as contains "United StatesZulu"
    Then I should see following grid:
      | Id | Country Name      |
      | 1  | United StatesZulu |
