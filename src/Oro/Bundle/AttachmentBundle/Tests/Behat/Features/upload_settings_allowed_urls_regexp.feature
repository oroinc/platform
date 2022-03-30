@ticket-BAP-20940
@regression

Feature: Upload Settings Allowed URLs Regexp
  In order to manage allowed external URLs
  As an administrator
  I should be able to specify allowed URLs regular expression in system configuration

  Scenario: Syntax error when regular expression is invalid
    Given I login as administrator
    And I go to System/ Configuration
    When I follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    And uncheck "Use default" for "Allowed URLs RegExp" field
    And I fill in "Allowed URLs RegExp" with "invalid~regexp"
    And I save form
    Then I should see validation errors:
      | Allowed URLs RegExp | This value is not a valid regular expression. Reason: "preg_match(): Unknown modifier 'r'" |

  Scenario: Correct regular expression is saved
    When I fill in "Allowed URLs RegExp" with "^http(s)?://example\.org/"
    And I save form
    Then I should see "Configuration saved" flash message
    And Allowed URLs RegExp field should have "^http(s)?://example\.org/" value
