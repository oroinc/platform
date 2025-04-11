@ticket-BAP-23008
Feature: Immutable enum options
  In order to manage data of enum fields
  As an Administrator
  I want to be able to disable editing of enum options if the is immutable configuration option is set

  Scenario: Check enum options are immutable
    Given I login as administrator
    When I go to System/ Entities/ Entity Management
    And filter Name as contains "Activity"
    And I click view "Marketing Activity" in grid
    And I click edit type in grid
    Then I should not see "Add" button
    And I should not see "Enum Option Remove Button"
