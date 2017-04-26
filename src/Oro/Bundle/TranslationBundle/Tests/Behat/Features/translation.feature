Feature: Confirmation while deleting translations
  Scenario: Delete translation for key oro.ui.create_entity
    Given I login as administrator
    When I go to System/Localization/Translations
    And filter Key as is equal to "oro.ui.create_entity"
    And I should see oro.ui.create_entity in grid with following data:
      | Translated Value | Create %entityName% |
    And I click Remove oro.ui.create_entity in grid
    Then I should see "Action Confirmation"
    And I should see "Delete translated value?"
    When I press "Yes, Delete"
    Then I should see oro.ui.create_entity in grid with following data:
      | Translated Value | |
    When filter Translated Value as is equal to "Create %entityName%"
    Then there is no records in grid
    And I click Logout in user menu
