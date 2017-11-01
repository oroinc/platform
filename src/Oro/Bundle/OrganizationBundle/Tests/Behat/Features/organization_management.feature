Feature: Organizations management
  In order to keep organize my business structure
  and to be sure that multi-organization functionality is in operation
  As administrator
  I need to manage organizations records

  Scenario: Create preconditions to ensure organization name
    Given I login as administrator
    And I go to System/ User Management/ Organizations
    And click "edit" on first row in grid
    And I fill form with:
      |Name         | Acme, Inc |
    When I save and close form
    Then I should see "Organization saved" flash message

  Scenario: Administrator creates new one organization
    Given I login as administrator
    And I go to System/ User Management/ Organizations
    And press "Create Organization"
    And I fill form with:
      |Status       |Active    |
      |Name         |Fake, Org |
      |Description  |Super Org |
      |Global Access|No        |
    When I save and close form
    Then I should see "Organization saved" flash message
    And I should see Organization with:
      |Name         |Fake, Org |
      |Description  |Super Org |
      |Global Access|No        |
    And I should see "Active" green status
    And I should see a "Organizations switcher" element

  Scenario: Administrator disables and edits organization
    Given I go to System/ User Management/ Organizations
    And I should see following grid:
      |Name     |Enabled|Global access|
      #|Acme, Inc|Active |             | BAP-15432
      |Acme, Inc|Active |No           |
      |Fake, Org|Active |No           |
    And I click Edit "Fake, Org" in grid
    And I fill form with:
      |Name         |False, Org     |
      |Status       |Inactive       |
      |Description  |Noe Super Org  |
      |Global Access|Yes            |
    When I save and close form
    Then I should see "Organization saved" flash message
    And I should see Organization with:
      |Name         |False, Org |
      |Description  |Super Org  |
      |Global Access|Yes        |
    And I should see "Inactive" gray status
    And I should not see a "Organizations switcher" element
    And I go to System/ User Management/ Organizations
    And I should see following grid:
      |Name      |Enabled |Global access|
      #|Acme, Inc |Active  |             | BAP-15432
      | Acme, Inc  | Active   | No  |
      | False, Org | Inactive | Yes |

  Scenario: Administrator changes organization
    Given I click Edit "False, Org" in grid
    And I fill form with:
      |Status       |Active         |
      |Global Access|No             |
    And I save and close form
    And I should see "Organization saved" flash message
    And I am logged in under False, Org organization
    And I should be on Admin Dashboard page
    And I go to System/ User Management/ Organizations
    And I click Edit "Acme, Inc" in grid
    And I fill form with:
      |Status       |Inactive       |
    And I save and close form
    And I should see "Organization saved" flash message
    When I am logged out
    And login as administrator
    Then I should not see a "Organizations switcher" element
    And I should see "FALSE, ORG" in the "Current Organization" element
    And I wait for action
