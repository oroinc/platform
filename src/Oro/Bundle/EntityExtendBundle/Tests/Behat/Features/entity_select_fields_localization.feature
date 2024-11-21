@regression
@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: Entity select fields localization
  In order to check whether the translations in the fields are working
  As a Administrator
  I create an additional entity with fields and provide two localizations

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    Then I click View User in grid

  Scenario: Create Extended entity`s field with option
    Given I click "Create Field"
    When I fill form with:
      | Field name | ACME   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label    |
      | ACME_Eng |
    And I save and close form

  Scenario: Update field`s options for second language
    Given I enable "Zulu_Loc" localization
    And I reload the page
    When I go to System/Entities/Entity Management
    And I filter Name as is equal to "User"
    And I click view User in grid
    When I click edit ACME in grid
    When I fill form with:
      | Label | AcmeEntity |
    And I fill "Entity Config Form" with:
      | Option First | ACME_Zulu |
    And I save form
    Then I should see "Field saved" flash message
    And I reload the page
    And "Entity Config Form" must contains values:
      | Option First | ACME_Zulu |

  Scenario: Check field`s options for default language
    Given I enable "English (United States)" localization
    When I reload the page
    Then "Entity Config Form" must contains values:
      | Option First | ACME_Eng |
