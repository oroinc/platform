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
      | Current Value       | Entities |
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
      | Current Value       | Maintenance Notifications |
      | English Translation | Maintenance Notifications |
