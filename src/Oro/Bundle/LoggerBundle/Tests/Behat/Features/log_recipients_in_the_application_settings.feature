@regression
@ticket-BAP-18677

Feature: Log recipients in the application settings
  In order to be able to manage the recipients who receive the error log notifications
  As an Administrator
  I add new email addresses and checking the validator on the application settings page

  Scenario: I check the application configuration form with invalid address
    Given I login as administrator
    And I go to System/Configuration
    And follow "System Configuration/General Setup/Application Settings" on configuration sidebar
    And uncheck "Use default" for "Recipients email addresses" field
    And fill form with:
      | Recipients Email Addresses | test1@@no-valid-email;test@example.com |
    When I click "Save settings"
    Then I should not see "Configuration saved" flash message
    And I should see validation errors:
      | Recipients Email Addresses | The "test1@@no-valid-email" is not valid email address. |

  Scenario: I check the application configuration form with invalid addresses
    And fill form with:
      | Recipients Email Addresses | test1@@no-valid-email;test@@examplecom |
    When I click "Save settings"
    Then I should not see "Configuration saved" flash message
    And I should see validation errors:
      | Recipients Email Addresses | The "test1@@no-valid-email, test@@examplecom" are not valid email addresses. |

  Scenario: I check the application configuration form with valid addresses
    And fill form with:
      | Recipients Email Addresses | test@example.com |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
