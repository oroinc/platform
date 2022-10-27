@regression
@ticket-BAP-20115
@fixture-OroIntegrationBundle:IntegrationsActiveAndInactive.yml

Feature: Integration enabled filtration

  Scenario: Check integration status filter
    Given I login as administrator
    And I go to System/Integrations/Manage Integrations
    Then there are two records in grid

    When I check "Active" strictly in "Status" filter
    Then there is one record in grid
    And I should see following grid containing rows:
      | Name                    | Status |
      | Test Integration active | Active |
    And I reset "Status" filter

    When I check "Inactive" strictly in "Status" filter
    Then there is one record in grid
    And I should see following grid containing rows:
      | Name                      | Status   |
      | Test Integration inactive | Inactive |
