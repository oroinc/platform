@ticket-BAP-17525
Feature: Configuration search visibility of validation errors
  In order to fill all required configuration options
  As an Administrator
  I would like to see validation errors even for fields that does not match search criteria

  Scenario: Fields with validation errors are always visible
    Given I login as administrator
    When I go to System/ Configuration
    Then I should see "Application URL"
    And I should not see "Use default" for "Application URL" field

    When I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then I should not see "Use default" for "URL" field
    And I should not see "Use default" for "Secure URL" field

    When I type "Secure" in "Configuration Quick Search"
    Then I should not see "Cookie Name"
    And I should not see "Product URL Prefix"
    
    When I type "" in "Configuration Quick Search"
    Then I should see "Cookie Name"
    And I should see "Product URL Prefix"

    When uncheck "Use default" for "Cookie Name" field
    And I type "" in "Cookie Name"
    And I click on empty space
    Then I should see "This value should not be blank"

    When I type "Secure" in "Configuration Quick Search"
    Then I should see "Cookie Name"
    And I should see "This value should not be blank"
    And I should not see "Product URL Prefix"

  Scenario: Checking Use default removes validation errors
    When check "Use default" for "Cookie Name" field
    Then I should not see "This value should not be blank"
    When uncheck "Use default" for "Cookie Name" field
    And click "Save settings"
    Then I should see "This value should not be blank"

  Scenario: Reset configuration changes
    When I press "Reset"
    And click "OK" in confirmation dialogue
    Then I should not see "This value should not be blank"
    And I should see "Product URL Prefix"

    When click "Save settings"
    And I should see "Configuration saved" flash message

  Scenario: Validation is applied not only for system scope
    Given I go to System/ Websites
    And click "Configuration" on row "Default" in grid

    When I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then I should see "Use Organization" for "URL" field
    And I should see "Use Organization" for "Secure URL" field

    When uncheck "Use Organization" for "Secure URL" field
    And I type "" in "Secure URL"
    And I click on empty space
    Then I should see "This value should not be blank"
