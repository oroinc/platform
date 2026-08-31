@skip
@regression
Feature: Configuration changes are recorded in Data Audit
  In order to keep an audit trail of configuration changes
  As an Administrator
  I need every configuration change, at any level, to appear in Data Audit as its own entity type,
  filterable by Entity Type and searchable by the changed data

  Scenario: A global change is audited as "Configuration: System"
    Given I login as administrator
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Application Settings" on configuration sidebar
    And uncheck "Use default" for "Recipients email addresses" field
    And I fill in "Recipients email addresses" with "test@oroinc.com"
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System/ Data Audit
    Then I should see "Configuration: System" in grid
    And I should see "Recipients email addresses" in grid

  Scenario: A change made in My Configuration is audited as "Configuration: User"
    Given I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use Organization" for "Record Pagination limit" field
    And I fill in "Record Pagination limit" with "42"
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System/ Data Audit
    Then I should see "Configuration: User" in grid
    And I should see "Record Pagination limit" in grid

  Scenario: Data Audit is filtered by configuration Entity Types
    Given I go to System/ Data Audit
    When I check "Configuration: System" in "Entity Type" filter
    And I check "Configuration: User" in "Entity Type" filter
    Then I should see "Configuration: System" in grid
    And I should see "Configuration: User" in grid
    And I should see "Recipients email addresses" in grid
    And I should see "Record Pagination limit" in grid

  Scenario: Data Audit is searched by the changed configuration data
    Given I go to System/ Data Audit
    When I filter "Data" as contains "Record Pagination limit"
    Then I should see "Configuration: User" in grid

  Scenario: Taking a value out of the parent scope is audited even though the value stays the same
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Application Settings" on configuration sidebar
    And check "Use default" for "Recipients email addresses" field
    And I submit form
    Then I should see "Configuration saved" flash message
    When I follow "System Configuration/General Setup/Application Settings" on configuration sidebar
    And uncheck "Use default" for "Recipients email addresses" field
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System/ Data Audit
    And I filter "Data" as contains "Recipients email addresses"
    Then the number of records greater than or equal to 3
    And I should see following grid containing rows:
      | Entity type           | Action |
      | Configuration: System | Create |

  Scenario: Giving a value back to the parent scope is audited as well
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Application Settings" on configuration sidebar
    And check "Use default" for "Recipients email addresses" field
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System/ Data Audit
    And I filter "Data" as contains "Recipients email addresses"
    Then the number of records greater than or equal to 4
    And I should see following grid containing rows:
      | Entity type           | Action |
      | Configuration: System | Remove |
