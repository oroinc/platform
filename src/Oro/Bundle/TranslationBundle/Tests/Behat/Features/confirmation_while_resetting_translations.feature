@regression
Feature: Confirmation while resetting translations
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Reset translation for key oro.ui.create_entity
    Given I login as administrator
    When I go to System/Localization/Translations
    And filter Key as is equal to "oro.ui.create_entity"
    And I should see oro.ui.create_entity in grid with following data:
      | Translated Value | Create %entityName% |
    And I click Reset oro.ui.create_entity in grid
    Then I should see "Reset Confirmation"
    And I should see "Reset translated value?"
    When I click "Reset" in modal window
    Then I should see oro.ui.create_entity in grid with following data:
      | Translated Value | |
    When filter Translated Value as is equal to "Create %entityName%"
    Then there is no records in grid
    And I click Logout in user menu
