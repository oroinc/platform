Feature: Check that all entity schema is updated after application install
  As an Administrator
  I want to be sure that all entity schema is updated after application install
  So I sort grid by Schema status

  Scenario: Sort Entity Management grid by Schema Status
    Given I login as administrator
    And I go to System/Entities/Entity Management
    When I sort grid by Schema status
    When I sort grid by Schema status again
    Then there is no "Requires update" in grid
