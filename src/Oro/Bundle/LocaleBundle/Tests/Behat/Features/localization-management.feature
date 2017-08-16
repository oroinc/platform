@regression
@ticket-BAP-12371
@automatically-ticket-tagged
@fixture-OroLocaleBundle:LocalizationFixture.yml
Feature: Localization management
  Manage available localizations

  Scenario: Open child localization
    Given I login as administrator
    When I go to System/Localization/Localizations
    Then I should see "Localization1" in grid with following data:
      | Title | Localization 1 |
    And I should see "Localization2" in grid with following data:
      | Title | Localization 2 |
    When I click View "Localization1" in grid
    Then there are 2 records in grid
    And I should see "Localization2" in grid with following data:
      | Title | Localization 2 |
    And I should see "Localization3" in grid with following data:
      | Title | Localization 3 |
    When I click View "Localization2" in grid
    Then there are 2 records in grid
    And I should see "Localization4" in grid with following data:
      | Title | Localization 4 |
    And I should see "Localization5" in grid with following data:
      | Title | Localization 5 |
