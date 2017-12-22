Feature: Check that all entity schema is updated after application install
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Sort Entity Management grid by Schema Status
    Given I login as administrator
    And I go to System/Entities/Entity Management
    When I sort grid by Schema status
    When I sort grid by Schema status again
    Then there is no "Requires update" in grid
