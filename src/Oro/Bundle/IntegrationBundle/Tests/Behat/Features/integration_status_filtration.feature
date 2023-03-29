@regression
@ticket-BAP-15769
@fixture-OroIntegrationBundle:IntegrationWithStatuses.yml
@behat-test-env
Feature: Integration status filtration

  Scenario: Check integration status filter
    Given I login as administrator
    And I go to System/Integrations/Manage Integrations
    When I click edit "Test Integration" in grid
    Then there are two records in grid

    When I check "Failed" in "Status" filter
    Then there is one record in grid
    And I should see following grid containing rows:
      | Status | Message       |
      | Failed | STATUS_FAILED |
    And I reset "Status" filter

    When I check "Completed" in "Status" filter
    Then there is one record in grid
    And I should see following grid containing rows:
      | Status    | Message          |
      | Completed | STATUS_COMPLETED |
