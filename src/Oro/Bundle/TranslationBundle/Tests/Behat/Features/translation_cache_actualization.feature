Feature: Translation cache actualization
  In order to apply translation changes on the fly (immediately)
  As a User
  I want to see a changed translation immediately after the change

  Scenario: Background
    Given I login as administrator

  Scenario: Change English translation
    When I go to System/Emails/Maintenance Notifications
    And I reload the page
    Then I should see "There are no maintenance notifications"
    When I go to System/Localization/Translations
    And I check "English" in Language filter
    And I filter Key as is equal to "oro.notification.massnotification.entity_plural_label"
    And I edit "oro.notification.massnotification.entity_plural_label" Translated Value as "Entities"
    Then I should see oro.notification.massnotification.entity_plural_label in grid with following data:
      | Translated Value    | Entities |
      | English Translation | Entities |
    And I go to System/Emails/Maintenance Notifications
    And I reload the page
    Then I should see "There are no entities"
    # Revert changed value to prevent other's features failures
    When I go to System/Localization/Translations
    And I check "English" in Language filter
    And I filter Key as is equal to "oro.notification.massnotification.entity_plural_label"
    And I edit "oro.notification.massnotification.entity_plural_label" Translated Value as "Maintenance Notifications"
    Then I should see oro.notification.massnotification.entity_plural_label in grid with following data:
      | Translated Value    | Maintenance Notifications |
      | English Translation | Maintenance Notifications |

  Scenario: JS translations editing for key oro.datagrid.gridView.actions
    Given I go to System/User Management/Users
    Then I should see "Options"
    Then I go to System/Localization/Translations
    And I filter Key as is equal to "oro.datagrid.gridView.actions"
    And I edit first record from grid:
      | Translated Value |  OptionsTranslatedValue |
    And I go to System/User Management/Users
    When I reload the page
    Then I should see "OptionsTranslatedValue"
