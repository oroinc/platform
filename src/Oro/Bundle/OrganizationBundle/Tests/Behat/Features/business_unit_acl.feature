@regression
@ticket-BB-15935
Feature: Business Unit Acl
  In order to restrict access to my business structure
  As administrator
  I need to be able to give specific permissions to users to access business units

  Scenario: Set Business Unit entity's view and create permissions to Division
    Given I login as administrator
    And I go to System/User Management/Roles
    And I click edit Administrator in grid
    And I select following permissions:
      | Business Unit | View:Division | Create:Division |
    And I save and close form
    Then I should see "Role saved" flash message

  Scenario: User with Division permission is able to create Business Unit with assigned Main Business Unit as a parent
    And I go to System/ User Management/ Business Units
    And click "Create Business Unit"
    And I fill form with:
      | Name                 | New Business Unit |
      | Parent Business Unit | Main              |
    And I check first one record in 0 column
    When I save and close form
    Then I should see "Business unit saved" flash message
    And I should see Business Unit with:
      | Name | New Business Unit |
