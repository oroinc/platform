Feature: Translation cache actualization
  In order to apply translation changes on the fly (immediately)
  As a User
  I want I want to see changed translation after translation cache actualized by reloading page

  Scenario: Reset translation for key oro.ui.create_entity
    Given I login as administrator
    When I go to System/Emails/Maintenance Notifications
    And I reload the page
    Then I should see "There are no entities"
    When I go to System/Localization/Translations
    And I filter Key as is equal to "oro.datagrid.no.entities"
    And I edit first record from grid:
      | Translated Value | Nothing to show |
    And I click "Update Cache"
    And I go to System/Emails/Maintenance Notifications
    And I reload the page
    Then I should see "Nothing to show"
    # Change back to prevent other's features failures
    When I go to System/Localization/Translations
    And I filter Key as is equal to "oro.datagrid.no.entities"
    And I edit first record from grid:
      | Translated Value | No records found |
    And I click "Update Cache"
    Then I should see oro.datagrid.no.entities in grid with following data:
      | Translated Value | No records found |
