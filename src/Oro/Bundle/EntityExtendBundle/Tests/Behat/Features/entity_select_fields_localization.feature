@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: Entity select fields localization
  In order to check whether the translations in the fields are working
  As a Administrator
  I create an additional entity with fields and provide two localizations

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name         | AcmeEntity   |
      | Label        | AcmeEntity   |
      | Plural Label | AcmeEntities |
    And I save and close form
    Then I should see "Entity saved" flash message

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
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Update field`s options for second language
    Given I enable "Zulu_Loc" localization
    And I reload the page
    And I filter Name as is equal to "AcmeEntity"
    And I click view AcmeEntity in grid
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
    Given I enable "English" localization
    When I reload the page
    Then "Entity Config Form" must contains values:
      | Option First | ACME_Eng |
